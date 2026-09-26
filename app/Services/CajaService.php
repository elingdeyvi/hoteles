<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaService
{
    public function cajaActiva(): Caja
    {
        $caja = Caja::query()->where('activo', true)->orderBy('id')->first();

        if (! $caja) {
            $caja = Caja::query()->create([
                'codigo' => 'recepcion',
                'nombre' => 'Recepción',
                'activo' => true,
            ]);
        }

        return $caja;
    }

    public function aperturaAbierta(): ?AperturaCaja
    {
        return $this->cajaActiva()->aperturaActual()->with('usuario')->first();
    }

    public function exigir(string $accion): AperturaCaja
    {
        $apertura = $this->aperturaAbierta();

        if ($apertura) {
            return $apertura;
        }

        $mensaje = $accion === 'cobro'
            ? 'Debe aperturar la caja antes de registrar un cobro.'
            : 'Debe aperturar la caja antes de cargar consumos.';

        throw ValidationException::withMessages(['caja' => [$mensaje]]);
    }

    public function abrir(float $fondo, ?string $observaciones, int $userId): AperturaCaja
    {
        $caja = $this->cajaActiva();

        if ($caja->aperturaActual()->exists()) {
            throw ValidationException::withMessages([
                'fondo_inicial' => ['La caja ya está abierta. Haga un corte Z para cerrarla.'],
            ]);
        }

        return AperturaCaja::query()->create([
            'caja_id' => $caja->id,
            'user_id' => $userId,
            'fondo_inicial' => $fondo,
            'fecha_apertura' => now(),
            'observaciones' => $observaciones,
            'cerrada' => false,
        ]);
    }

    /**
     * @return array<string, float>
     */
    public function totales(AperturaCaja $apertura): array
    {
        $consumos = (float) FolioCharge::query()
            ->where('apertura_caja_id', $apertura->id)
            ->where('charge_type', 'pos')
            ->selectRaw('COALESCE(SUM(amount * quantity), 0) as total')
            ->value('total');

        $pagos = FolioPayment::query()
            ->where('apertura_caja_id', $apertura->id)
            ->get(['payment_method', 'amount']);

        $efectivo = 0.0;
        $tarjeta = 0.0;
        $transferencia = 0.0;
        $otros = 0.0;

        foreach ($pagos as $pago) {
            $monto = (float) $pago->amount;
            match ($pago->payment_method) {
                'efectivo' => $efectivo += $monto,
                'tarjeta' => $tarjeta += $monto,
                'transferencia' => $transferencia += $monto,
                default => $otros += $monto,
            };
        }

        $fondo = (float) $apertura->fondo_inicial;

        return [
            'fondo_inicial' => $fondo,
            'total_consumos' => round($consumos, 2),
            'total_efectivo' => round($efectivo, 2),
            'total_tarjeta' => round($tarjeta, 2),
            'total_transferencia' => round($transferencia, 2),
            'total_otros' => round($otros, 2),
            'total_cobros' => round($efectivo + $tarjeta + $transferencia + $otros, 2),
            'total_esperado' => round($fondo + $efectivo, 2),
        ];
    }

    public function sincronizar(AperturaCaja $apertura): AperturaCaja
    {
        $apertura->update($this->totales($apertura));

        return $apertura->fresh(['usuario', 'caja']);
    }

    public function cortar(AperturaCaja $apertura, string $tipo, ?float $totalReal, ?string $observaciones, int $userId): CorteCaja
    {
        if ($apertura->cerrada) {
            throw ValidationException::withMessages(['tipo' => ['La caja ya está cerrada.']]);
        }

        if ($tipo === CorteCaja::TIPO_Z && $totalReal === null) {
            throw ValidationException::withMessages([
                'total_real' => ['El corte Z pide el efectivo contado en caja.'],
            ]);
        }

        return DB::transaction(function () use ($apertura, $tipo, $totalReal, $observaciones, $userId) {
            $totales = $this->totales($apertura);
            $apertura->update($totales);

            $diferencia = $totalReal === null ? null : round($totalReal - $totales['total_esperado'], 2);

            $corte = CorteCaja::query()->create([
                ...$totales,
                'apertura_caja_id' => $apertura->id,
                'caja_id' => $apertura->caja_id,
                'user_id' => $userId,
                'tipo' => $tipo,
                'fecha_corte' => now(),
                'total_real' => $totalReal,
                'diferencia' => $diferencia,
                'observaciones' => $observaciones,
            ]);

            if ($tipo === CorteCaja::TIPO_Z) {
                $apertura->update([
                    'cerrada' => true,
                    'fecha_cierre' => now(),
                    'total_real' => $totalReal,
                    'diferencia' => $diferencia,
                    'observaciones' => $observaciones ?: $apertura->observaciones,
                ]);
            }

            return $corte;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function paraVista(): ?array
    {
        $apertura = $this->aperturaAbierta();

        if (! $apertura) {
            return null;
        }

        return [
            'id' => $apertura->id,
            'fondo_inicial' => (float) $apertura->fondo_inicial,
            'fecha_apertura' => $apertura->fecha_apertura?->format('Y-m-d H:i'),
            'usuario' => $apertura->usuario?->name,
        ];
    }
}

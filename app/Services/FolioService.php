<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use App\Models\PosProduct;
use App\Models\Stay;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FolioService
{
    public function __construct(
        private readonly CajaService $caja,
        private readonly InventarioService $inventario,
    ) {}
    public function createForStay(Stay $stay, float $roomCharge): Folio
    {
        $folio = Folio::create([
            'stay_id' => $stay->id,
            'folio_number' => $this->generateNumber(),
            'balance' => $roomCharge,
            'status' => 'abierto',
        ]);

        FolioCharge::create([
            'folio_id' => $folio->id,
            'concept' => 'Estancia habitación',
            'charge_type' => 'habitacion',
            'amount' => $roomCharge,
            'quantity' => 1,
        ]);

        return $folio->load(['charges', 'payments']);
    }

    public function addCharge(Folio $folio, string $concept, float $amount, string $type = 'extra'): FolioCharge
    {
        $charge = FolioCharge::create([
            'folio_id' => $folio->id,
            'concept' => $concept,
            'charge_type' => $type,
            'amount' => $amount,
            'quantity' => 1,
        ]);

        $this->recalculateBalance($folio);

        return $charge;
    }

    public function removeCharge(Folio $folio, FolioCharge $charge, ?int $userId): void
    {
        if ($folio->status !== 'abierto') {
            throw ValidationException::withMessages([
                'folio' => ['El folio está cerrado.'],
            ]);
        }

        if ((int) $charge->folio_id !== (int) $folio->id) {
            abort(404);
        }

        if ($charge->charge_type === 'habitacion') {
            throw ValidationException::withMessages([
                'cargo' => ['La habitación no se puede quitar del folio.'],
            ]);
        }

        DB::transaction(function () use ($folio, $charge, $userId): void {
            if ($charge->charge_type === 'pos' && $charge->pos_product_id) {
                $product = PosProduct::query()->find($charge->pos_product_id);
                if ($product) {
                    $this->inventario->devolverVenta($product, (float) $charge->quantity, $userId, $charge->id);
                }
            }

            $aperturaId = $charge->apertura_caja_id;
            $charge->delete();
            $this->recalculateBalance($folio);

            if ($aperturaId) {
                $apertura = AperturaCaja::query()->find($aperturaId);
                if ($apertura && ! $apertura->cerrada) {
                    $this->caja->sincronizar($apertura);
                }
            }
        });
    }

    public function registerPayment(Folio $folio, string $method, float $amount, ?int $userId, ?string $reference = null): FolioPayment
    {
        $apertura = $this->caja->exigir('cobro');

        $payment = FolioPayment::create([
            'folio_id' => $folio->id,
            'payment_method' => $method,
            'amount' => $amount,
            'recibido' => $amount,
            'cambio' => 0,
            'reference' => $reference,
            'received_by' => $userId,
            'apertura_caja_id' => $apertura->id,
        ]);

        $this->recalculateBalance($folio);
        $this->caja->sincronizar($apertura);

        return $payment;
    }

    /**
     * Cobra el saldo y cierra el folio. En efectivo, el sobrante es el cambio.
     *
     * @return array{cambio: float, saldo: float}
     */
    public function cobrarYCerrar(Folio $folio, string $method, ?float $recibido, ?int $userId, ?string $reference = null): array
    {
        if ($folio->status !== 'abierto') {
            throw ValidationException::withMessages([
                'folio' => ['El folio ya está cerrado.'],
            ]);
        }

        $this->recalculateBalance($folio);
        $saldo = round((float) $folio->balance, 2);
        $cambio = 0.0;

        if ($saldo > 0) {
            $apertura = $this->caja->exigir('cobro');
            $recibidoRedondo = round((float) $recibido, 2);

            if ($method === 'efectivo') {
                if ($recibidoRedondo + 0.001 < $saldo) {
                    throw ValidationException::withMessages([
                        'recibido' => ['El efectivo recibido no cubre el saldo de '.$saldo.'.'],
                    ]);
                }
                $cambio = round($recibidoRedondo - $saldo, 2);
            }

            FolioPayment::create([
                'folio_id' => $folio->id,
                'payment_method' => $method,
                'amount' => $saldo,
                'recibido' => $method === 'efectivo' ? $recibidoRedondo : $saldo,
                'cambio' => $cambio,
                'reference' => $reference,
                'received_by' => $userId,
                'apertura_caja_id' => $apertura->id,
            ]);

            $this->recalculateBalance($folio);
            $this->caja->sincronizar($apertura);
        }

        $this->close($folio);

        return ['cambio' => $cambio, 'saldo' => $saldo];
    }

    public function recalculateBalance(Folio $folio): void
    {
        $folio->load(['charges', 'payments']);

        $charges = $folio->charges->sum(fn (FolioCharge $c) => (float) $c->amount * (int) $c->quantity);
        $payments = $folio->payments->sum(fn (FolioPayment $p) => (float) $p->amount);

        $folio->update(['balance' => round($charges - $payments, 2)]);
    }

    public function close(Folio $folio): Folio
    {
        $this->recalculateBalance($folio);

        $folio->update([
            'status' => 'cerrado',
            'closed_at' => now(),
        ]);

        return $folio->fresh(['charges', 'payments']);
    }

    private function generateNumber(): string
    {
        $prefix = config('hotel.folio_stay_prefix', 'FOL');

        return $prefix.'-'.now()->format('ymdHis').'-'.strtoupper(Str::random(3));
    }
}

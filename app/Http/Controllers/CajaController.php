<?php

namespace App\Http\Controllers;

use App\Models\CorteCaja;
use App\Models\MovimientoCaja;
use App\Services\CajaService;
use App\Support\CurrentProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CajaController extends Controller
{
    public function __construct(private readonly CajaService $caja) {}

    public function index(): Response
    {
        $apertura = $this->caja->aperturaAbierta();
        $resumen = $apertura ? $this->caja->totales($apertura) : null;

        $cortes = CorteCaja::query()
            ->with('usuario')
            ->where('caja_id', $this->caja->cajaActiva()->id)
            ->latest('fecha_corte')
            ->limit(30)
            ->get();

        return Inertia::render('Hotel/Caja/Index', [
            'apertura' => $apertura ? [
                'id' => $apertura->id,
                'fondo_inicial' => (float) $apertura->fondo_inicial,
                'fecha_apertura' => $apertura->fecha_apertura?->format('d/m/Y H:i'),
                'usuario' => $apertura->usuario?->name,
                'observaciones' => $apertura->observaciones,
            ] : null,
            'resumen' => $resumen,
            'movimientos' => MovimientoCaja::query()
                ->with('usuario')
                ->where('caja_id', $this->caja->cajaActiva()->id)
                ->latest('fecha_movimiento')
                ->limit(40)
                ->get()
                ->map(fn (MovimientoCaja $mov) => [
                    'id' => $mov->id,
                    'tipo' => $mov->tipo,
                    'concepto' => $mov->concepto,
                    'monto' => (float) $mov->monto,
                    'referencia' => $mov->referencia,
                    'fecha' => $mov->fecha_movimiento?->format('d/m/Y H:i'),
                    'usuario' => $mov->usuario?->name,
                    'abierta' => $apertura && (int) $mov->apertura_caja_id === (int) $apertura->id,
                ]),
            'cortes' => $cortes->map(fn (CorteCaja $corte) => [
                'id' => $corte->id,
                'tipo' => $corte->tipo,
                'fecha_corte' => $corte->fecha_corte?->format('d/m/Y H:i'),
                'usuario' => $corte->usuario?->name,
                'total_consumos' => (float) $corte->total_consumos,
                'total_cobros' => (float) $corte->total_cobros,
                'total_efectivo' => (float) $corte->total_efectivo,
                'total_ingresos' => (float) $corte->total_ingresos,
                'total_egresos' => (float) $corte->total_egresos,
                'total_esperado' => (float) $corte->total_esperado,
                'total_real' => $corte->total_real !== null ? (float) $corte->total_real : null,
                'diferencia' => $corte->diferencia !== null ? (float) $corte->diferencia : null,
            ]),
        ]);
    }

    public function abrir(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fondo_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $this->caja->abrir((float) $data['fondo_inicial'], $data['observaciones'] ?? null, $request->user()->id);

        return back()->with('success', 'Caja aperturada. Ya se pueden cargar consumos y cobros.');
    }

    public function cortar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:X,Z'],
            'total_real' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'conteo' => ['nullable', 'array'],
            'conteo.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $apertura = $this->caja->aperturaAbierta();
        if (! $apertura) {
            return back()->with('error', 'No hay una caja abierta.');
        }

        $conteo = $data['conteo'] ?? null;
        $contado = is_array($conteo) ? $this->caja->efectivoContado($conteo) : null;
        $totalReal = $contado && $contado > 0
            ? $contado
            : (isset($data['total_real']) && $data['total_real'] !== '' ? (float) $data['total_real'] : null);

        $corte = $this->caja->cortar(
            $apertura,
            $data['tipo'],
            $totalReal,
            $data['observaciones'] ?? null,
            $request->user()->id,
            $conteo
        );

        $mensaje = $corte->tipo === 'Z'
            ? 'Corte Z registrado. La caja quedó cerrada.'
            : 'Corte X registrado. La caja sigue abierta.';

        return back()->with('success', $mensaje);
    }

    public function movimiento(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:ingreso,egreso'],
            'concepto' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'referencia' => ['nullable', 'string', 'max:100'],
        ]);

        $apertura = $this->caja->exigir('cobro');
        MovimientoCaja::query()->create([
            'apertura_caja_id' => $apertura->id,
            'caja_id' => $apertura->caja_id,
            'user_id' => $request->user()->id,
            'tipo' => $data['tipo'],
            'concepto' => $data['concepto'],
            'monto' => $data['monto'],
            'referencia' => $data['referencia'] ?? null,
            'fecha_movimiento' => now(),
        ]);
        $this->caja->sincronizar($apertura);

        $texto = $data['tipo'] === 'ingreso' ? 'Ingreso registrado.' : 'Egreso registrado.';

        return back()->with('success', $texto);
    }

    public function eliminarMovimiento(MovimientoCaja $movimiento): RedirectResponse
    {
        abort_unless((int) $movimiento->caja?->property_id === (int) CurrentProperty::id(), 404);

        $apertura = $movimiento->apertura;
        if (! $apertura || $apertura->cerrada) {
            return back()->with('error', 'No se puede quitar un movimiento de una caja cerrada.');
        }

        $movimiento->delete();
        $this->caja->sincronizar($apertura);

        return back()->with('success', 'Movimiento eliminado.');
    }
}

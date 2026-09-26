<?php

namespace App\Http\Controllers;

use App\Models\CorteCaja;
use App\Services\CajaService;
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
            'cortes' => $cortes->map(fn (CorteCaja $corte) => [
                'id' => $corte->id,
                'tipo' => $corte->tipo,
                'fecha_corte' => $corte->fecha_corte?->format('d/m/Y H:i'),
                'usuario' => $corte->usuario?->name,
                'total_consumos' => (float) $corte->total_consumos,
                'total_cobros' => (float) $corte->total_cobros,
                'total_efectivo' => (float) $corte->total_efectivo,
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
        ]);

        $apertura = $this->caja->aperturaAbierta();
        if (! $apertura) {
            return back()->with('error', 'No hay una caja abierta.');
        }

        $corte = $this->caja->cortar(
            $apertura,
            $data['tipo'],
            isset($data['total_real']) ? (float) $data['total_real'] : null,
            $data['observaciones'] ?? null,
            $request->user()->id
        );

        $mensaje = $corte->tipo === 'Z'
            ? 'Corte Z registrado. La caja quedó cerrada.'
            : 'Corte X registrado. La caja sigue abierta.';

        return back()->with('success', $mensaje);
    }
}

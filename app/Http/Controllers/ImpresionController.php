<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\CorteCaja;
use App\Models\Folio;
use App\Models\Impresora;
use App\Models\PosVenta;
use App\Models\PrintAgentToken;
use App\Models\PrintJob;
use App\Models\Reservation;
use App\Services\HotelTicketText;
use App\Services\PrintJobService;
use App\Support\CatalogDelete;
use App\Support\CurrentProperty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ImpresionController extends Controller
{
    public function __construct(
        private readonly HotelTicketText $tickets,
        private readonly PrintJobService $jobs,
    ) {}

    public function index(): Response
    {
        $propertyId = CurrentProperty::id();
        $umbral = now()->subSeconds((int) config('printing.agent_offline_umbral', 20));

        return Inertia::render('Hotel/Printing/Index', [
            'modo' => config('printing.mode'),
            'agenteEnLinea' => $this->jobs->agenteEnLinea($propertyId),
            'impresoras' => Impresora::query()->orderByDesc('es_default')->orderBy('nombre')->get(),
            'agentes' => PrintAgentToken::query()->orderBy('nombre')->get()->map(fn (PrintAgentToken $agente) => [
                'id' => $agente->id,
                'nombre' => $agente->nombre,
                'activo' => $agente->activo,
                'en_linea' => $agente->activo && $agente->last_seen_at && $agente->last_seen_at->gte($umbral),
                'last_seen_at' => $agente->last_seen_at?->format('Y-m-d H:i'),
            ]),
            'trabajos' => PrintJob::query()->latest()->limit(20)->get(['id', 'tipo', 'estado', 'error_mensaje', 'created_at', 'completed_at']),
        ]);
    }

    public function storeImpresora(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'nombre_sistema' => ['nullable', 'string', 'max:120'],
            'ip' => ['nullable', 'string', 'max:45'],
            'puerto' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ancho_papel' => ['required', 'integer', 'in:58,80'],
            'es_default' => ['boolean'],
        ]);

        if ($request->boolean('es_default')) {
            Impresora::query()->update(['es_default' => false]);
        }

        Impresora::query()->create([
            ...$data,
            'puerto' => $data['puerto'] ?? 9100,
            'activo' => true,
            'es_default' => $request->boolean('es_default') || ! Impresora::query()->exists(),
        ]);

        return back()->with('success', 'Impresora guardada.');
    }

    public function updateImpresora(Request $request, Impresora $impresora): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'nombre_sistema' => ['nullable', 'string', 'max:120'],
            'ip' => ['nullable', 'string', 'max:45'],
            'puerto' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ancho_papel' => ['required', 'integer', 'in:58,80'],
            'activo' => ['boolean'],
            'es_default' => ['boolean'],
        ]);

        if ($request->boolean('es_default')) {
            Impresora::query()->where('id', '!=', $impresora->id)->update(['es_default' => false]);
        }

        $impresora->update([
            ...$data,
            'puerto' => $data['puerto'] ?? 9100,
            'activo' => $request->boolean('activo'),
            'es_default' => $request->boolean('es_default'),
        ]);

        return back()->with('success', 'Impresora actualizada.');
    }

    public function destroyImpresora(Impresora $impresora): RedirectResponse
    {
        return CatalogDelete::run($impresora, [
            'trabajos de impresión' => PrintJob::query()->where('impresora_id', $impresora->id)->exists(),
        ], 'Impresora eliminada.');
    }

    public function crearToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
        ]);

        $plano = Str::random(48);
        PrintAgentToken::query()->create([
            'nombre' => $data['nombre'],
            'token_hash' => PrintAgentToken::hashToken($plano),
            'activo' => true,
        ]);

        return back()->with('success', 'Token del agente (cópielo ahora, no se vuelve a mostrar): '.$plano);
    }

    public function imprimirReserva(Reservation $reservation): JsonResponse
    {
        $reservation->load(['huesped', 'roomType', 'room']);
        $impresora = $this->impresora();
        $columnas = $impresora?->destino()->columnas() ?? 42;
        $contenido = $this->tickets->reserva($reservation, $this->empresa(), $columnas);

        if (! $impresora) {
            return response()->json($this->sinImpresora($contenido));
        }

        return response()->json($this->jobs->despachar(
            PrintJob::TIPO_RESERVA,
            $contenido,
            $impresora,
            request()->user(),
            $reservation->id,
        ));
    }

    public function imprimirFolio(Folio $folio): JsonResponse
    {
        $folio->load(['stay.reservation.huesped', 'stay.room', 'charges', 'payments']);
        $impresora = $this->impresora();
        $columnas = $impresora?->destino()->columnas() ?? 42;
        $contenido = $this->tickets->folio($folio, $this->empresa(), $columnas);

        if (! $impresora) {
            return response()->json($this->sinImpresora($contenido));
        }

        return response()->json($this->jobs->despachar(
            PrintJob::TIPO_FOLIO,
            $contenido,
            $impresora,
            request()->user(),
            null,
            $folio->id,
        ));
    }

    public function imprimirVenta(PosVenta $venta): JsonResponse
    {
        $venta->load('lineas');
        $impresora = $this->impresora();
        $columnas = $impresora?->destino()->columnas() ?? 42;
        $contenido = $this->tickets->ventaPublica($venta, $this->empresa(), $columnas);

        if (! $impresora) {
            return response()->json($this->sinImpresora($contenido));
        }

        return response()->json($this->jobs->despachar(
            'venta',
            $contenido,
            $impresora,
            request()->user(),
        ));
    }

    public function imprimirCorte(CorteCaja $corte): JsonResponse
    {
        $corte->load('usuario', 'caja');
        abort_unless((int) $corte->caja?->property_id === (int) CurrentProperty::id(), 404);

        $impresora = $this->impresora();
        $columnas = $impresora?->destino()->columnas() ?? 42;
        $contenido = $this->tickets->corte($corte, $this->empresa(), $columnas);

        if (! $impresora) {
            return response()->json($this->sinImpresora($contenido));
        }

        return response()->json($this->jobs->despachar(
            'corte',
            $contenido,
            $impresora,
            request()->user(),
        ));
    }

    public function probar(Impresora $impresora): JsonResponse
    {
        $contenido = $this->tickets->prueba($this->empresa(), $impresora->nombre, $impresora->destino()->columnas());

        return response()->json($this->jobs->despachar(
            PrintJob::TIPO_PRUEBA,
            $contenido,
            $impresora,
            request()->user(),
        ));
    }

    private function impresora(): ?Impresora
    {
        return Impresora::query()->where('activo', true)->orderByDesc('es_default')->orderBy('id')->first();
    }

    private function empresa(): ?ConfiguracionEmpresa
    {
        return ConfiguracionEmpresa::obtenerConfiguracion(CurrentProperty::id());
    }

    /**
     * @return array{success: bool, message: string, codigo: string, lineas: list<string>}
     */
    private function sinImpresora(string $contenido): array
    {
        return [
            'success' => false,
            'message' => 'No hay impresora activa. Se imprime en este equipo.',
            'codigo' => 'sin_impresora',
            'lineas' => preg_split("/\r\n|\n|\r/", $contenido) ?: [],
        ];
    }
}

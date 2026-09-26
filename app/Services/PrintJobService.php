<?php

namespace App\Services;

use App\Models\Impresora;
use App\Models\PrintAgentToken;
use App\Models\PrintJob;
use App\Models\User;
use App\Support\CurrentProperty;
use App\Support\ImpresoraDestino;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrintJobService
{
    public function __construct(private readonly ImpresionService $impresion) {}

    public function usaCola(): bool
    {
        return config('printing.mode') === 'queue';
    }

    public function agenteEnLinea(?int $propertyId = null): bool
    {
        $propertyId ??= CurrentProperty::id();
        if (! $propertyId) {
            return false;
        }

        $umbral = now()->subSeconds((int) config('printing.agent_offline_umbral', 20));

        return PrintAgentToken::withoutGlobalScopes()
            ->where('property_id', $propertyId)
            ->where('activo', true)
            ->where('last_seen_at', '>=', $umbral)
            ->exists();
    }

    /**
     * @return array{success: bool, message: string, codigo: string, print_job_id?: int, lineas: list<string>}
     */
    public function despachar(string $tipo, string $contenido, Impresora $impresora, ?User $user, ?int $reservationId = null, ?int $folioId = null): array
    {
        $lineas = preg_split("/\r\n|\n|\r/", $contenido) ?: [];
        $destino = $impresora->destino();

        if ($this->usaCola()) {
            if (! $this->agenteEnLinea($impresora->property_id)) {
                return $this->navegador('No hay agente de impresión en línea. Se imprime en este equipo.', 'sin_agente', $lineas);
            }

            $job = PrintJob::query()->create([
                'property_id' => $impresora->property_id,
                'impresora_id' => $impresora->id,
                'user_id' => $user?->id,
                'reservation_id' => $reservationId,
                'folio_id' => $folioId,
                'tipo' => $tipo,
                'estado' => PrintJob::ESTADO_PENDING,
                'contenido' => $contenido,
                'columnas' => $destino->columnas(),
                'impresora_snapshot' => [
                    'nombre' => $impresora->nombre,
                    'nombre_sistema' => $impresora->nombre_sistema,
                    'ip' => $impresora->ip,
                    'puerto' => $impresora->puerto,
                    'ancho_papel' => $impresora->ancho_papel,
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Ticket en cola. El agente local lo imprimirá en breve.',
                'codigo' => 'en_cola',
                'print_job_id' => $job->id,
                'lineas' => $lineas,
            ];
        }

        if (! $destino->tieneDestino()) {
            return $this->navegador('La impresora no tiene nombre de Windows ni IP. Se imprime en este equipo.', 'sin_impresora', $lineas);
        }

        $resultado = $this->impresion->imprimir($destino, $contenido);
        if (! ($resultado['success'] ?? false)) {
            return $this->navegador($resultado['error'] ?? 'No se pudo imprimir. Se imprime en este equipo.', 'sin_impresora', $lineas);
        }

        return [
            'success' => true,
            'message' => $resultado['message'] ?? 'Ticket enviado a la impresora.',
            'codigo' => 'impreso',
            'lineas' => $lineas,
        ];
    }

    /**
     * @return Collection<int, PrintJob>
     */
    public function reclamarPendientes(PrintAgentToken $agente, int $limite = 5): Collection
    {
        return DB::transaction(function () use ($agente, $limite) {
            $jobs = PrintJob::withoutGlobalScopes()
                ->where('property_id', $agente->property_id)
                ->where('estado', PrintJob::ESTADO_PENDING)
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($limite)
                ->get();

            foreach ($jobs as $job) {
                $job->update([
                    'estado' => PrintJob::ESTADO_PROCESSING,
                    'started_at' => now(),
                    'intentos' => $job->intentos + 1,
                ]);
            }

            return $jobs;
        });
    }

    public function ejecutarJob(PrintJob $job): array
    {
        $snap = is_array($job->impresora_snapshot) ? $job->impresora_snapshot : [];
        $destino = new ImpresoraDestino(
            $snap['nombre_sistema'] ?? null,
            $snap['ip'] ?? null,
            (int) ($snap['puerto'] ?? 9100),
            (int) ($snap['ancho_papel'] ?? 80),
            $snap['nombre'] ?? null,
        );

        return $this->impresion->imprimir($destino, (string) $job->contenido);
    }

    public function marcarCompletado(PrintJob $job): void
    {
        $job->update([
            'estado' => PrintJob::ESTADO_COMPLETED,
            'completed_at' => now(),
            'error_mensaje' => null,
        ]);
    }

    public function marcarFallido(PrintJob $job, string $error): void
    {
        $estado = $job->intentos >= $job->max_intentos
            ? PrintJob::ESTADO_FAILED
            : PrintJob::ESTADO_PENDING;

        $job->update([
            'estado' => $estado,
            'error_mensaje' => $error,
            'started_at' => $estado === PrintJob::ESTADO_PENDING ? null : $job->started_at,
        ]);
    }

    public function agentePuedeGestionar(PrintAgentToken $agente, PrintJob $job): bool
    {
        return (int) $job->property_id === (int) $agente->property_id;
    }

    /**
     * @param  list<string>  $lineas
     * @return array{success: bool, message: string, codigo: string, lineas: list<string>}
     */
    private function navegador(string $mensaje, string $codigo, array $lineas): array
    {
        return [
            'success' => false,
            'message' => $mensaje,
            'codigo' => $codigo,
            'lineas' => $lineas,
        ];
    }
}

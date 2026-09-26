<?php

namespace App\Http\Controllers;

use App\Models\PrintAgentToken;
use App\Models\PrintJob;
use App\Services\PrintJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintAgentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var PrintAgentToken $agente */
        $agente = $request->attributes->get('print_agent');

        return response()->json([
            'success' => true,
            'agente' => [
                'id' => $agente->id,
                'nombre' => $agente->nombre,
                'property_id' => $agente->property_id,
            ],
            'modo' => config('printing.mode'),
        ]);
    }

    public function pending(Request $request, PrintJobService $jobs): JsonResponse
    {
        /** @var PrintAgentToken $agente */
        $agente = $request->attributes->get('print_agent');
        $limite = min(10, max(1, (int) $request->input('limit', 5)));
        $reclamados = $jobs->reclamarPendientes($agente, $limite);

        return response()->json([
            'data' => $reclamados->map(fn (PrintJob $job) => [
                'id' => $job->id,
                'tipo' => $job->tipo,
                'contenido' => $job->contenido,
                'columnas' => $job->columnas,
                'impresora' => $job->impresora_snapshot,
            ]),
        ]);
    }

    public function complete(Request $request, PrintJob $printJob, PrintJobService $jobs): JsonResponse
    {
        /** @var PrintAgentToken $agente */
        $agente = $request->attributes->get('print_agent');
        if (! $jobs->agentePuedeGestionar($agente, $printJob)) {
            return response()->json(['errors' => ['print_job' => ['Trabajo no autorizado para este agente.']]], 403);
        }

        $jobs->marcarCompletado($printJob);

        return response()->json(['success' => true, 'message' => 'Trabajo completado.']);
    }

    public function fail(Request $request, PrintJob $printJob, PrintJobService $jobs): JsonResponse
    {
        /** @var PrintAgentToken $agente */
        $agente = $request->attributes->get('print_agent');
        if (! $jobs->agentePuedeGestionar($agente, $printJob)) {
            return response()->json(['errors' => ['print_job' => ['Trabajo no autorizado para este agente.']]], 403);
        }

        $jobs->marcarFallido($printJob, (string) ($request->input('error') ?: 'Error al imprimir.'));

        return response()->json([
            'success' => true,
            'estado' => $printJob->fresh()?->estado,
        ]);
    }
}

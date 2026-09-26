<?php

namespace App\Http\Middleware;

use App\Models\PrintAgentToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatePrintAgent
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->header('X-Print-Agent-Token') ?? $request->bearerToken();

        if (! is_string($token) || trim($token) === '') {
            return response()->json([
                'errors' => ['token' => ['Token de agente de impresión requerido.']],
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $agente = PrintAgentToken::withoutGlobalScopes()
            ->where('token_hash', PrintAgentToken::hashToken(trim($token)))
            ->where('activo', true)
            ->first();

        if (! $agente) {
            return response()->json([
                'errors' => ['token' => ['Token de agente inválido o inactivo.']],
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $agente->forceFill(['last_seen_at' => now()])->saveQuietly();
        $request->attributes->set('print_agent', $agente);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $role
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        $user = auth()->user();

        // Verificar si el usuario tiene el rol requerido
        if (!$user->hasRole($role)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso',
                'required_role' => $role,
                'user_roles' => $user->getRoleNames()
            ], 403);
        }

        return $next($request);
    }
}

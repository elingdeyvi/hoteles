<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateWithTokenQuery
{
    /**
     * Handle an incoming request.
     * 
     * Extrae el token de la query string y lo agrega al header Authorization
     * para que Sanctum pueda autenticar la petición.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si ya hay un token en el header
        $existingToken = $request->bearerToken();
        
        // Si no hay token en el header Authorization pero hay uno en la query string
        if (!$existingToken && $request->has('token')) {
            $token = $request->query('token');
            if ($token && !empty(trim($token))) {
                // Limpiar el token (eliminar espacios en blanco y decodificar si viene codificado)
                $token = trim($token);
                // Decodificar el token si viene codificado en la URL
                $token = urldecode($token);
                
                // Agregar el token al header Authorization
                // Usar mergeHeaders para asegurar que se establezca correctamente
                $request->headers->set('Authorization', 'Bearer ' . $token);
                
                // Verificar que se estableció correctamente
                $newToken = $request->bearerToken();
                
                // Log para depuración
                \Log::info('Token extraído de query string y agregado al header', [
                    'token_preview' => substr($token, 0, 20) . '...',
                    'token_length' => strlen($token),
                    'url' => $request->fullUrl(),
                    'token_establecido' => $newToken !== null,
                    'header_authorization' => $request->header('Authorization') ? 'presente' : 'ausente'
                ]);
                
                // Si después de establecer el header aún no se puede leer, intentar otra forma
                if (!$newToken) {
                    \Log::warning('Token no se pudo establecer correctamente en el header', [
                        'url' => $request->fullUrl()
                    ]);
                }
            } else {
                \Log::warning('Token en query string está vacío o es inválido', [
                    'url' => $request->fullUrl(),
                    'token_value' => $token
                ]);
            }
        } else {
            // Log cuando no hay token en query string
            if (!$existingToken && !$request->has('token')) {
                \Log::debug('No hay token en header ni en query string', [
                    'url' => $request->fullUrl()
                ]);
            }
        }
        
        return $next($request);
    }
}

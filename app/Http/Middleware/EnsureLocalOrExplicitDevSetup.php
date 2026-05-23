<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas peligrosas (storage:link, key:generate) solo en local/testing o si ALLOW_DEV_SETUP_ROUTES=true.
 */
class EnsureLocalOrExplicitDevSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        if (filter_var(config('app.allow_dev_setup_routes', false), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}

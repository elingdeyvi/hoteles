<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'property.context' => \App\Http\Middleware\SetCurrentProperty::class,
            'print.agent' => \App\Http\Middleware\AuthenticatePrintAgent::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'booking/webhooks/stripe',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // IONOS rechaza el estado 419 y lo convierte en 502 "Upstream server failed".
        // Se contesta 422 para quedarse en la misma pantalla, con la cookie nueva.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $mensaje = 'No se completó. Sigues en esta pantalla: vuelve a intentarlo.';

            if ($request->header('X-Inertia')) {
                $response->setStatusCode(422);
                $response->headers->set('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    'message' => $mensaje,
                    'errors' => ['sesion' => [$mensaje]],
                ], JSON_UNESCAPED_UNICODE));

                return $response;
            }

            $redir = redirect()->back()->with('error', $mensaje);
            foreach ($response->headers->getCookies() as $cookie) {
                $redir->headers->setCookie($cookie);
            }

            return $redir;
        });
    })->create();

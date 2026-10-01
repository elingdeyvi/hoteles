<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ReturnsJsonOrRedirect
{
    protected function jsonOrRedirect(
        Request $request,
        mixed $data,
        string $redirectRoute,
        string $message,
    ): JsonResponse|RedirectResponse {
        // El alta rápida (axios) pide JSON. Inertia también manda X-Requested-With y debe recibir un redirect.
        if (! $request->header('X-Inertia') && ($request->expectsJson() || $request->wantsJson())) {
            return response()->json([
                'data' => $data,
                'message' => $message,
            ], JsonResponse::HTTP_CREATED);
        }

        return redirect()->route($redirectRoute)->with('success', $message);
    }
}

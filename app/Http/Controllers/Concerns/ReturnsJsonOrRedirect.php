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
        // Acceso rápido (axios) manda X-Requested-With / Accept: application/json.
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'data' => $data,
                'message' => $message,
            ], JsonResponse::HTTP_CREATED);
        }

        return redirect()->route($redirectRoute)->with('success', $message);
    }
}

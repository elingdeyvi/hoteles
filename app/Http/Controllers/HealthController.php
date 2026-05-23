<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';

        try {
            DB::connection()->getPdo();
            DB::connection()->select('select 1');
        } catch (\Throwable) {
            $dbStatus = 'error';
        }

        $healthy = $dbStatus === 'ok';

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'database' => $dbStatus,
            'version' => config('hotel.health.version', '1.0.0'),
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}

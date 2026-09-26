<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Session-authenticated AJAX helpers can be added here if needed.
| Primary app routes live in web.php (Inertia + session).
|
*/

Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});

Route::prefix('print-agent')->middleware('print.agent')->group(function () {
    Route::get('/heartbeat', [\App\Http\Controllers\PrintAgentController::class, 'heartbeat']);
    Route::get('/jobs/pending', [\App\Http\Controllers\PrintAgentController::class, 'pending']);
    Route::post('/jobs/{printJob}/complete', [\App\Http\Controllers\PrintAgentController::class, 'complete']);
    Route::post('/jobs/{printJob}/fail', [\App\Http\Controllers\PrintAgentController::class, 'fail']);
});

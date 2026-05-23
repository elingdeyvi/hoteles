<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    public function board(): JsonResponse
    {
        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('number')
            ->get()
            ->groupBy('status');

        return response()->json(['data' => $rooms]);
    }

    public function updateStatus(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:disponible,limpia,sucia,mantenimiento,ocupada'],
        ]);

        if ($room->status === 'ocupada' && $data['status'] !== 'ocupada') {
            $hasActiveStay = $room->stays()->where('status', 'activa')->exists();
            if ($hasActiveStay) {
                return response()->json(['message' => 'La habitación tiene huésped activo.'], 422);
            }
        }

        $room->update(['status' => $data['status']]);

        return response()->json(['data' => $room->fresh('roomType')]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HousekeepingController extends Controller
{
    public function board(): Response
    {
        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('number')
            ->get();

        return Inertia::render('Hotel/Housekeeping/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function updateStatus(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:disponible,limpia,sucia,mantenimiento,ocupada'],
        ]);

        if ($room->status === 'ocupada' && $data['status'] !== 'ocupada') {
            $hasActiveStay = $room->stays()->where('status', 'activa')->exists();
            if ($hasActiveStay) {
                return back()->with('error', 'La habitación tiene huésped activo.');
            }
        }

        $room->update(['status' => $data['status']]);

        return back()->with('success', 'Estado de habitación actualizado.');
    }
}

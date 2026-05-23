<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Stay;
use App\Services\FolioService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInOutController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly FolioService $folios
    ) {}

    public function checkIn(Request $request, Reservation $reservation): JsonResponse
    {
        if (in_array($reservation->status, ['cancelada', 'check_out'], true)) {
            return response()->json(['message' => 'Reserva no válida para check-in.'], 422);
        }

        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $room = Room::findOrFail($data['room_id']);
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        if (! $this->reservations->isRoomAvailable($room, $checkIn, $checkOut, $reservation->id)) {
            return response()->json(['message' => 'Habitación no disponible.'], 422);
        }

        $stay = Stay::create([
            'reservation_id' => $reservation->id,
            'room_id' => $room->id,
            'checked_in_at' => now(),
            'status' => 'activa',
        ]);

        $reservation->update([
            'room_id' => $room->id,
            'status' => 'check_in',
        ]);

        $room->update(['status' => 'ocupada']);

        $folio = $this->folios->createForStay($stay, (float) $reservation->estimated_total);

        return response()->json([
            'data' => [
                'reservation' => $reservation->fresh(['huesped', 'room']),
                'stay' => $stay->load('room'),
                'folio' => $folio,
            ],
        ]);
    }

    public function checkOut(Reservation $reservation): JsonResponse
    {
        $stay = $reservation->stays()->where('status', 'activa')->latest()->first();

        if (! $stay) {
            return response()->json(['message' => 'No hay estancia activa.'], 422);
        }

        $folio = $stay->folio;

        if ($folio && $folio->status === 'abierto' && (float) $folio->balance > 0) {
            return response()->json(['message' => 'El folio tiene saldo pendiente. Registre pagos antes del check-out.'], 422);
        }

        if ($folio && $folio->status === 'abierto') {
            $this->folios->close($folio);
        }

        $stay->update([
            'checked_out_at' => now(),
            'status' => 'finalizada',
        ]);

        $reservation->update(['status' => 'check_out']);
        $stay->room?->update(['status' => 'sucia']);

        return response()->json([
            'data' => $reservation->fresh(['huesped', 'room', 'stays.folio']),
        ]);
    }
}

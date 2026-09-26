<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Stay;
use App\Services\FolioService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckInOutController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly FolioService $folios
    ) {}

    public function show(Reservation $reservation): Response
    {
        $reservation->load(['huesped', 'roomType', 'room', 'stays.folio']);
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        $rooms = Room::query()
            ->where('room_type_id', $reservation->room_type_id)
            ->where('is_active', true)
            ->orderBy('number')
            ->get()
            ->filter(fn (Room $room) => $this->reservations->isRoomAvailable($room, $checkIn, $checkOut, $reservation->id))
            ->values();

        return Inertia::render('Hotel/CheckIn', [
            'reservation' => $reservation,
            'availableRooms' => $rooms,
        ]);
    }

    public function checkIn(Request $request, Reservation $reservation): RedirectResponse
    {
        if (in_array($reservation->status, ['cancelada', 'check_out'], true)) {
            return back()->with('error', 'Reserva no válida para check-in.');
        }

        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $room = Room::findOrFail($data['room_id']);
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        if (! $this->reservations->isRoomAvailable($room, $checkIn, $checkOut, $reservation->id)) {
            return back()->with('error', 'Habitación no disponible.');
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

        return redirect()->route('folios.show', $folio)->with('success', 'Check-in registrado. Folio abierto.');
    }

    public function checkOut(Reservation $reservation): RedirectResponse
    {
        $stay = $reservation->stays()->where('status', 'activa')->latest()->first();

        if (! $stay) {
            return back()->with('error', 'No hay estancia activa.');
        }

        $folio = $stay->folio;

        if ($folio && $folio->status === 'abierto' && (float) $folio->balance > 0) {
            return redirect()
                ->route('folios.show', $folio)
                ->with('error', 'El folio tiene saldo pendiente. Registre pagos antes del check-out.');
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

        return back()->with('success', 'Check-out realizado. La habitación quedó sucia.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelPlanningController extends Controller
{
    public function index(Request $request): Response
    {
        $date = Carbon::parse($request->get('date', now()->toDateString()));
        $from = $date->copy()->startOfMonth();
        $to = $date->copy()->endOfMonth();

        $reservations = Reservation::query()
            ->with(['huesped', 'roomType', 'room'])
            ->whereNotIn('status', ['cancelada'])
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('check_in', [$from, $to])
                    ->orWhereBetween('check_out', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to): void {
                        $inner->where('check_in', '<=', $from)
                            ->where('check_out', '>=', $to);
                    });
            })
            ->orderBy('check_in')
            ->get();

        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('number')
            ->get();

        $inHouse = Reservation::query()
            ->with(['huesped', 'roomType'])
            ->whereNotIn('status', ['cancelada', 'check_out'])
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->get();

        $byRoomId = $inHouse->whereNotNull('room_id')->keyBy('room_id');

        $board = $rooms->map(function (Room $room) use ($byRoomId) {
            $reservation = $byRoomId->get($room->id);

            return [
                'room' => $room,
                'reservation' => $reservation,
                'occupied' => $reservation !== null || $room->status === 'ocupada',
            ];
        });

        return Inertia::render('Hotel/Planning/Index', [
            'date' => $date->toDateString(),
            'events' => $reservations,
            'board' => $board,
            'unassigned' => $inHouse->whereNull('room_id')->values(),
        ]);
    }
}

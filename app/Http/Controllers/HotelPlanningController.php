<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelPlanningController extends Controller
{
    /**
     * Eventos para FullCalendar (planning de reservas).
     */
    public function calendar(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->get('start', now()->startOfMonth()));
        $to = Carbon::parse($request->get('end', now()->endOfMonth()));

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
            ->get();

        $events = $reservations->map(function (Reservation $r) {
            $roomLabel = $r->room?->number ?? $r->roomType?->name ?? 'Sin habitación';

            return [
                'id' => $r->id,
                'title' => "{$r->huesped?->nombre} · {$roomLabel}",
                'start' => $r->check_in->format('Y-m-d'),
                'end' => $r->check_out->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $this->colorForStatus($r->status),
                'borderColor' => $this->colorForStatus($r->status),
                'extendedProps' => [
                    'folio' => $r->folio,
                    'status' => $r->status,
                    'room_type' => $r->roomType?->name,
                    'room_number' => $r->room?->number,
                    'guests_count' => $r->guests_count,
                    'estimated_total' => (float) $r->estimated_total,
                ],
            ];
        });

        return response()->json(['data' => $events]);
    }

    /**
     * Matriz habitación × reserva para una fecha (ocupación del día).
     */
    public function roomBoard(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->get('date', now()->toDateString()));

        $rooms = Room::query()
            ->with('roomType')
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('number')
            ->get();

        $reservations = Reservation::query()
            ->with(['huesped', 'roomType'])
            ->whereNotIn('status', ['cancelada', 'check_out'])
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->get();

        $byRoomId = $reservations->whereNotNull('room_id')->keyBy('room_id');

        $board = $rooms->map(function (Room $room) use ($byRoomId, $date) {
            $reservation = $byRoomId->get($room->id);

            return [
                'room' => $room,
                'reservation' => $reservation,
                'occupied' => $reservation !== null || $room->status === 'ocupada',
            ];
        });

        $unassigned = $reservations->whereNull('room_id')->values();

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'rooms' => $board,
                'unassigned_reservations' => $unassigned,
                'summary' => [
                    'total_rooms' => $rooms->count(),
                    'occupied' => $board->where('occupied', true)->count(),
                    'available' => $board->where('occupied', false)->where(fn ($r) => ! in_array($r['room']->status, ['mantenimiento', 'sucia'], true))->count(),
                ],
            ],
        ]);
    }

    private function colorForStatus(string $status): string
    {
        return match ($status) {
            'check_in' => '#22c55e',
            'confirmada' => '#1e5f8a',
            'pendiente' => '#64748b',
            'check_out' => '#c4a35a',
            default => '#94a3b8',
        };
    }
}

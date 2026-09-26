<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelPlanningController extends Controller
{
    public function index(Request $request): Response
    {
        $date = Carbon::parse($request->get('date', now()->toDateString()))->startOfDay();

        return Inertia::render('Hotel/Planning/Index', [
            'date' => $date->toDateString(),
            'today' => now()->toDateString(),
            'board' => $this->buildBoard($date),
        ]);
    }

    /**
     * Eventos para FullCalendar (ocupación mensual).
     */
    public function calendar(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->get('start', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->get('end', now()->endOfMonth()->toDateString()))->endOfDay();

        $events = Reservation::query()
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
            ->get()
            ->map(function (Reservation $r) {
                $roomLabel = $r->room?->number ?? $r->roomType?->name ?? 'Sin habitación';

                return [
                    'id' => $r->id,
                    'title' => ($r->huesped?->nombre ?? 'Huésped').' · '.$roomLabel,
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
                        'check_in' => $r->check_in->format('Y-m-d'),
                        'check_out' => $r->check_out->format('Y-m-d'),
                    ],
                ];
            })
            ->values();

        return response()->json(['data' => $events]);
    }

    /**
     * Matriz habitación × reserva para una fecha.
     */
    public function roomBoard(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->get('date', now()->toDateString()))->startOfDay();

        return response()->json([
            'data' => $this->buildBoard($date),
        ]);
    }

    /**
     * @return array{date: string, rooms: mixed, unassigned_reservations: mixed, summary: array{total_rooms: int, occupied: int, available: int}}
     */
    private function buildBoard(Carbon $date): array
    {
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

        $board = $rooms->map(function (Room $room) use ($byRoomId) {
            $reservation = $byRoomId->get($room->id);

            return [
                'room' => $room->only(['id', 'number', 'floor', 'status', 'room_type_id']) + [
                    'room_type' => $room->roomType?->only(['id', 'name']),
                ],
                'reservation' => $reservation ? [
                    'id' => $reservation->id,
                    'folio' => $reservation->folio,
                    'status' => $reservation->status,
                    'huesped' => $reservation->huesped?->only(['id', 'nombre']),
                ] : null,
                'occupied' => $reservation !== null || $room->status === 'ocupada',
            ];
        });

        $unassigned = $reservations->whereNull('room_id')->values()->map(fn (Reservation $r) => [
            'id' => $r->id,
            'folio' => $r->folio,
            'status' => $r->status,
            'huesped' => $r->huesped?->only(['id', 'nombre']),
        ]);

        return [
            'date' => $date->toDateString(),
            'rooms' => $board,
            'unassigned_reservations' => $unassigned,
            'summary' => [
                'total_rooms' => $rooms->count(),
                'occupied' => $board->where('occupied', true)->count(),
                'available' => $board
                    ->where('occupied', false)
                    ->filter(fn ($row) => ! in_array($row['room']['status'], ['mantenimiento', 'sucia'], true))
                    ->count(),
            ],
        ];
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

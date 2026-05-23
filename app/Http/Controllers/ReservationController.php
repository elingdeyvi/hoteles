<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\HotelPricingService;
use App\Services\OnlineBookingService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly HotelPricingService $pricing,
        private readonly OnlineBookingService $onlineBooking
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()
            ->with(['huesped', 'roomType', 'room'])
            ->orderByDesc('check_in');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('from')) {
            $query->whereDate('check_in', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('check_out', '<=', $request->date('to'));
        }

        return response()->json(['data' => $query->paginate(min(100, max(1, (int) $request->get('per_page', 20))))]);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        $types = RoomType::query()->when($data['room_type_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->where('is_active', true)
            ->get();

        $result = $types->map(function (RoomType $type) use ($checkIn, $checkOut) {
            return [
                'room_type' => $type,
                'available_rooms' => $this->reservations->availableRoomsCount($type, $checkIn, $checkOut),
                'estimated_total' => $this->pricing->estimateStayTotal($type, $checkIn, $checkOut),
                'nightly_rate' => $this->pricing->priceForNight($type, $checkIn),
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'huesped_id' => ['required', 'exists:huespedes,id'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $roomType = RoomType::findOrFail($data['room_type_id']);

        if (! empty($data['room_id'])) {
            $room = Room::findOrFail($data['room_id']);
            if (! $this->reservations->isRoomAvailable($room, $checkIn, $checkOut)) {
                return response()->json(['message' => 'La habitación no está disponible en esas fechas.'], 422);
            }
        }

        $reservation = Reservation::create([
            ...$data,
            'folio' => $this->reservations->generateFolio(),
            'source' => 'recepcion',
            'status' => 'confirmada',
            'estimated_total' => $this->pricing->estimateStayTotal($roomType, $checkIn, $checkOut),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $reservation->load(['huesped', 'roomType', 'room'])], 201);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json([
            'data' => $reservation->load(['huesped', 'roomType', 'room', 'stays.folio']),
        ]);
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'check_in' => ['sometimes', 'date'],
            'check_out' => ['sometimes', 'date', 'after:check_in'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation->update($data);

        if ($reservation->roomType && $reservation->check_in && $reservation->check_out) {
            $reservation->update([
                'estimated_total' => $this->pricing->estimateStayTotal(
                    $reservation->roomType,
                    Carbon::parse($reservation->check_in),
                    Carbon::parse($reservation->check_out)
                ),
            ]);
        }

        return response()->json(['data' => $reservation->fresh(['huesped', 'roomType', 'room'])]);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        if ($reservation->status === 'check_in') {
            return response()->json(['message' => 'No se puede cancelar una reserva con check-in activo.'], 422);
        }

        $reservation->update(['status' => 'cancelada']);

        return response()->json(['data' => $reservation]);
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        try {
            $reservation = $this->onlineBooking->confirmWebReservation($reservation);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        return response()->json(['data' => $reservation]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Huesped;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\HotelPricingService;
use App\Services\OnlineBookingService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly HotelPricingService $pricing,
        private readonly OnlineBookingService $onlineBooking
    ) {}

    public function index(Request $request): Response
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

        $availability = null;
        if ($request->filled('avail_in') && $request->filled('avail_out')) {
            $checkIn = Carbon::parse($request->get('avail_in'));
            $checkOut = Carbon::parse($request->get('avail_out'));
            if ($checkOut->gt($checkIn)) {
                $availability = RoomType::query()->where('is_active', true)->orderBy('name')->get()->map(function (RoomType $type) use ($checkIn, $checkOut) {
                    $free = $this->reservations->availableRooms($type, $checkIn, $checkOut);

                    return [
                        'room_type' => $type->only(['id', 'name', 'base_price', 'capacity']),
                        'available_rooms' => $free->count(),
                        'room_ids' => $free->pluck('id')->values(),
                        'estimated_total' => $this->pricing->estimateStayTotal($type, $checkIn, $checkOut),
                        'nightly_rate' => $this->pricing->priceForNight($type, $checkIn),
                    ];
                });
            }
        }

        return Inertia::render('Hotel/Reservations/Index', [
            'reservations' => $query->paginate(20)->withQueryString(),
            'huespedes' => Huesped::query()->orderBy('nombre')->get(['id', 'nombre', 'email']),
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'base_price']),
            'rooms' => Room::query()->where('is_active', true)->orderBy('number')->get(['id', 'number', 'room_type_id', 'status']),
            'filters' => $request->only(['status', 'source', 'from', 'to', 'avail_in', 'avail_out']),
            'availability' => $availability,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
                throw ValidationException::withMessages([
                    'room_id' => 'La habitación no está disponible en esas fechas.',
                ]);
            }
        } elseif ($this->reservations->availableRoomsCount($roomType, $checkIn, $checkOut) < 1) {
            throw ValidationException::withMessages([
                'room_type_id' => 'No hay habitaciones disponibles de ese tipo en esas fechas.',
            ]);
        }

        Reservation::create([
            ...$data,
            'guests_count' => $data['guests_count'] ?? 1,
            'folio' => $this->reservations->generateFolio(),
            'source' => 'recepcion',
            'status' => 'confirmada',
            'estimated_total' => $this->pricing->estimateStayTotal($roomType, $checkIn, $checkOut),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Reserva confirmada.');
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation->update($data);

        if ($reservation->roomType) {
            $reservation->update([
                'estimated_total' => $this->pricing->estimateStayTotal(
                    $reservation->roomType,
                    Carbon::parse($reservation->check_in),
                    Carbon::parse($reservation->check_out)
                ),
            ]);
        }

        return back()->with('success', 'Reserva actualizada.');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status === 'check_in') {
            return back()->with('error', 'No se puede cancelar una reserva con check-in activo.');
        }

        $reservation->update(['status' => 'cancelada']);

        return back()->with('success', 'Reserva cancelada.');
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        try {
            $this->onlineBooking->confirmWebReservation($reservation);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Reserva confirmada.');
    }
}

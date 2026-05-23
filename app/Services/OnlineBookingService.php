<?php

namespace App\Services;

use App\Models\Huesped;
use App\Models\Reservation;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineBookingService
{
    public function __construct(
        private readonly ReservationService $reservations,
        private readonly HotelPricingService $pricing,
        private readonly ReservationNotificationService $notifications,
        private readonly BookingPaymentService $payments
    ) {}

    public function activeRoomTypes()
    {
        return RoomType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description', 'capacity', 'amenities', 'base_price']);
    }

    public function availability(Carbon $checkIn, Carbon $checkOut, ?int $roomTypeId = null): array
    {
        $types = RoomType::query()
            ->when($roomTypeId, fn ($q) => $q->where('id', $roomTypeId))
            ->where('is_active', true)
            ->get();

        return $types->map(function (RoomType $type) use ($checkIn, $checkOut) {
            $available = $this->reservations->availableRoomsCount($type, $checkIn, $checkOut);

            return [
                'room_type' => $type->only(['id', 'name', 'code', 'description', 'capacity', 'amenities', 'base_price']),
                'available_rooms' => $available,
                'estimated_total' => $this->pricing->estimateStayTotal($type, $checkIn, $checkOut),
                'nightly_rate' => $this->pricing->priceForNight($type, $checkIn),
            ];
        })->values()->all();
    }

    public function createBooking(array $data): Reservation
    {
        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();
        $minAdvance = (int) config('hotel.booking.min_advance_days', 0);

        if ($checkIn->lt(now()->startOfDay()->addDays($minAdvance))) {
            throw ValidationException::withMessages([
                'check_in' => ['La fecha de entrada no es válida.'],
            ]);
        }

        $roomType = RoomType::query()->where('is_active', true)->findOrFail($data['room_type_id']);
        $guests = (int) ($data['guests_count'] ?? 1);

        if ($guests > $roomType->capacity) {
            throw ValidationException::withMessages([
                'guests_count' => ["Este tipo de habitación admite hasta {$roomType->capacity} huéspedes."],
            ]);
        }

        if ($this->reservations->availableRoomsCount($roomType, $checkIn, $checkOut) < 1) {
            throw ValidationException::withMessages([
                'room_type_id' => ['No hay disponibilidad para las fechas seleccionadas.'],
            ]);
        }

        $huesped = $this->findOrCreateGuest($data['guest']);

        $reservation = Reservation::create([
            'folio' => $this->reservations->generateFolio(),
            'online_reference' => 'WEB-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
            'source' => 'web',
            'huesped_id' => $huesped->id,
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => $guests,
            'status' => 'pendiente',
            'estimated_total' => $this->pricing->estimateStayTotal($roomType, $checkIn, $checkOut),
            'notes' => $data['notes'] ?? null,
            'created_by' => null,
        ]);

        $this->notifications->sendReceived($reservation->load(['huesped', 'roomType']));

        if ($this->payments->paymentsEnabled()) {
            $this->payments->applyPendingPayment($reservation);
            $reservation = $reservation->fresh(['huesped', 'roomType']);
        }

        return $reservation;
    }

    public function lookup(string $folio, string $email): ?Reservation
    {
        return Reservation::query()
            ->with(['huesped', 'roomType'])
            ->where('folio', $folio)
            ->whereHas('huesped', fn ($q) => $q->where('email', $email))
            ->first();
    }

    public function confirmWebReservation(Reservation $reservation): Reservation
    {
        if ($reservation->source !== 'web' || $reservation->status !== 'pendiente') {
            throw ValidationException::withMessages([
                'status' => ['Solo se pueden confirmar reservas web pendientes.'],
            ]);
        }

        $roomType = $reservation->roomType;
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        if (! $roomType || $this->reservations->availableRoomsCount($roomType, $checkIn, $checkOut) < 1) {
            throw ValidationException::withMessages([
                'availability' => ['Ya no hay disponibilidad para confirmar esta reserva.'],
            ]);
        }

        $reservation->update(['status' => 'confirmada']);

        $reservation = $reservation->fresh(['huesped', 'roomType', 'room']);
        $this->notifications->sendConfirmed($reservation);

        return $reservation;
    }

    private function findOrCreateGuest(array $guest): Huesped
    {
        $email = strtolower(trim($guest['email']));

        $existing = Huesped::query()->where('email', $email)->first();
        if ($existing) {
            $existing->update([
                'nombre' => $guest['nombre'],
                'telefono' => $guest['telefono'] ?? $existing->telefono,
                'documento' => $guest['documento'] ?? $existing->documento,
            ]);

            return $existing;
        }

        return Huesped::create([
            'nombre' => $guest['nombre'],
            'email' => $email,
            'telefono' => $guest['telefono'] ?? null,
            'documento' => $guest['documento'] ?? null,
        ]);
    }
}

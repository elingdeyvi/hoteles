<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ReservationService
{
    public function __construct(
        private readonly HotelPricingService $pricing
    ) {}

    public function generateFolio(): string
    {
        $prefix = config('hotel.folio_prefix', 'RES');

        return $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
    }

    public function isRoomAvailable(Room $room, Carbon $checkIn, Carbon $checkOut, ?int $excludeReservationId = null): bool
    {
        $query = Reservation::query()
            ->where('room_id', $room->id)
            ->whereNotIn('status', ['cancelada', 'check_out'])
            ->where(function ($q) use ($checkIn, $checkOut): void {
                $q->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                    ->orWhere(function ($inner) use ($checkIn, $checkOut): void {
                        $inner->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return ! $query->exists() && $room->is_active && ! in_array($room->status, ['mantenimiento'], true);
    }

    public function availableRoomsCount(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): int
    {
        $rooms = Room::query()
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->get();

        return $rooms->filter(fn (Room $room) => $this->isRoomAvailable($room, $checkIn, $checkOut))->count();
    }
}

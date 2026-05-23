<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ReservationService;
use Carbon\Carbon;
use Database\Seeders\HotelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_is_not_available_when_overlapping_reservation_exists(): void
    {
        $this->seed(HotelSeeder::class);

        $roomType = RoomType::query()->firstOrFail();
        $room = Room::query()->where('room_type_id', $roomType->id)->firstOrFail();

        $checkIn = Carbon::parse(now()->addDays(5));
        $checkOut = Carbon::parse(now()->addDays(8));

        Reservation::create([
            'property_id' => $roomType->property_id,
            'folio' => 'RES-TEST-001',
            'huesped_id' => \App\Models\Huesped::query()->firstOrFail()->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmada',
            'estimated_total' => 1000,
            'source' => 'recepcion',
        ]);

        $service = app(ReservationService::class);

        $this->assertFalse($service->isRoomAvailable($room, $checkIn, $checkOut));
        $this->assertTrue($service->isRoomAvailable($room, $checkIn->copy()->addDays(10), $checkOut->copy()->addDays(12)));
    }
}

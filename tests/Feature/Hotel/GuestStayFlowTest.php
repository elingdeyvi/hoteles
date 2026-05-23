<?php

namespace Tests\Feature\Hotel;

use App\Models\Folio;
use App\Models\Huesped;
use App\Models\PosProduct;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Tests\HotelTestCase;

class GuestStayFlowTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHotelCatalog();
    }

    public function test_full_flow_reservation_checkin_pos_payment_checkout(): void
    {
        $huesped = Huesped::query()->firstOrFail();
        $roomType = RoomType::query()->where('code', 'sencilla')->firstOrFail();
        $room = Room::query()->where('room_type_id', $roomType->id)->where('status', 'disponible')->firstOrFail();

        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(3)->toDateString();

        $reservationResponse = $this->postJson('/api/hotel/reservations', [
            'huesped_id' => $huesped->id,
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 1,
        ]);

        $reservationResponse->assertCreated();
        $reservationId = $reservationResponse->json('data.id');

        $checkInResponse = $this->postJson("/api/hotel/reservations/{$reservationId}/check-in", [
            'room_id' => $room->id,
        ]);

        $checkInResponse->assertOk()
            ->assertJsonPath('data.reservation.status', 'check_in');

        $folioId = $checkInResponse->json('data.folio.id');
        $this->assertNotNull($folioId);

        $product = PosProduct::query()->where('name', 'Mojito')->firstOrFail();

        $this->postJson('/api/hotel/pos/charge-to-folio', [
            'folio_id' => $folioId,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $folio = Folio::query()->findOrFail($folioId);
        $balance = (float) $folio->fresh()->balance;
        $this->assertGreaterThan(0, $balance);

        $this->postJson("/api/hotel/folios/{$folioId}/payments", [
            'payment_method' => 'efectivo',
            'amount' => $balance,
        ])->assertOk();

        $this->assertEquals(0, (float) Folio::findOrFail($folioId)->balance);

        $this->postJson("/api/hotel/reservations/{$reservationId}/check-out")
            ->assertOk()
            ->assertJsonPath('data.status', 'check_out');

        $room->refresh();
        $this->assertEquals('sucia', $room->status);

        $folio->refresh();
        $this->assertEquals('cerrado', $folio->status);
    }

    public function test_checkout_blocked_when_folio_has_balance(): void
    {
        $huesped = Huesped::query()->firstOrFail();
        $roomType = RoomType::query()->firstOrFail();
        $room = Room::query()->where('room_type_id', $roomType->id)->firstOrFail();

        $reservationId = $this->postJson('/api/hotel/reservations', [
            'huesped_id' => $huesped->id,
            'room_type_id' => $roomType->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/hotel/reservations/{$reservationId}/check-in", ['room_id' => $room->id]);

        $this->postJson("/api/hotel/reservations/{$reservationId}/check-out")
            ->assertStatus(422);

        $this->assertEquals('check_in', Reservation::findOrFail($reservationId)->status);
    }
}

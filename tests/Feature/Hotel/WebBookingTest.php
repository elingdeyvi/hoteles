<?php

namespace Tests\Feature\Hotel;

use App\Mail\WebReservationMail;
use App\Models\Reservation;
use App\Models\RoomType;
use Illuminate\Support\Facades\Mail;
use Tests\HotelTestCase;

class WebBookingTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHotelCatalog();
        Mail::fake();
    }

    public function test_guest_can_create_web_reservation_and_lookup_status(): void
    {
        $roomType = RoomType::query()->where('code', 'doble')->firstOrFail();

        $checkIn = now()->addDays(3)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $create = $this->postJson('/api/booking/costa-azul/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest' => [
                'nombre' => 'Ana Pérez',
                'email' => 'ana.perez@test.com',
                'telefono' => '5550001111',
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.source', 'web');

        $folio = $create->json('data.folio');
        $this->assertNotEmpty($folio);

        Mail::assertSent(WebReservationMail::class, function (WebReservationMail $mail) {
            return $mail->type === 'received';
        });

        $this->getJson('/api/booking/costa-azul/reservations/lookup?'.http_build_query([
            'folio' => $folio,
            'email' => 'ana.perez@test.com',
        ]))
            ->assertOk()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.room_type.name', 'Doble');
    }

    public function test_reception_can_confirm_web_reservation(): void
    {
        $roomType = RoomType::query()->firstOrFail();
        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        $this->postJson('/api/booking/costa-azul/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guest' => [
                'nombre' => 'Carlos Ruiz',
                'email' => 'carlos@test.com',
            ],
        ])->assertCreated();

        $reservation = Reservation::query()->where('source', 'web')->latest('id')->firstOrFail();

        $this->actingAsAdmin()
            ->postJson("/api/hotel/reservations/{$reservation->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmada');

        Mail::assertSent(WebReservationMail::class, function (WebReservationMail $mail) {
            return $mail->type === 'confirmed';
        });
    }
}

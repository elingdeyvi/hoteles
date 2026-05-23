<?php

namespace Tests\Feature\Hotel;

use App\Models\Property;
use App\Models\RoomType;
use Tests\HotelTestCase;

class MultiPropertyTest extends HotelTestCase
{
    public function test_properties_list_returns_default_hotel(): void
    {
        $this->seedHotelCatalog();

        $this->actingAsAdmin()
            ->getJson('/api/hotel/properties')
            ->assertOk()
            ->assertJsonFragment(['code' => 'costa-azul', 'name' => 'Hotel Costa Azul'])
            ->assertJsonFragment(['code' => 'sierra-verde', 'name' => 'Hotel Sierra Verde'])
            ->assertJsonCount(2, 'data');
    }

    public function test_room_types_scoped_by_property_header(): void
    {
        $this->seedHotelCatalog();

        $costa = Property::query()->where('code', 'costa-azul')->firstOrFail();
        $sierra = Property::query()->where('code', 'sierra-verde')->firstOrFail();

        $this->actingAsAdmin()
            ->withHeader('X-Property-Id', (string) $costa->id)
            ->getJson('/api/hotel/room-types')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['code' => 'doble']);

        $this->actingAsAdmin()
            ->withHeader('X-Property-Id', (string) $sierra->id)
            ->getJson('/api/hotel/room-types')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['code' => 'cabana']);
    }

    public function test_guest_can_book_second_property_via_slug(): void
    {
        $this->seedHotelCatalog();

        $roomType = RoomType::query()
            ->where('code', 'cabana')
            ->whereHas('property', fn ($q) => $q->where('code', 'sierra-verde'))
            ->firstOrFail();

        $checkIn = now()->addDays(4)->toDateString();
        $checkOut = now()->addDays(6)->toDateString();

        $this->postJson('/api/booking/sierra-verde/reservations', [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'guest' => [
                'nombre' => 'Luis Montaña',
                'email' => 'luis.montana@test.com',
                'telefono' => '5559998888',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.room_type.name', 'Cabaña');
    }
}

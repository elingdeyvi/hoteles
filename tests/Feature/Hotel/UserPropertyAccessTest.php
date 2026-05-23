<?php

namespace Tests\Feature\Hotel;

use App\Models\Property;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\HotelTestCase;

class UserPropertyAccessTest extends HotelTestCase
{
    public function test_recepcionist_only_sees_assigned_properties(): void
    {
        $this->seedHotelCatalog();

        $costa = Property::query()->where('code', 'costa-azul')->firstOrFail();

        $this->actingAsAdmin()
            ->postJson('/api/hotel/properties', [
                'name' => 'Hotel Sierra',
                'code' => 'sierra',
                'booking_enabled' => true,
            ])
            ->assertCreated();

        $recepcion = User::query()->where('email', 'recepcionista@gmail.com')->firstOrFail();
        $recepcion->properties()->sync([$costa->id => ['is_default' => true]]);

        Sanctum::actingAs($recepcion, ['*']);

        $this->getJson('/api/hotel/properties')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'costa-azul');
    }

    public function test_user_cannot_access_unassigned_property_via_header(): void
    {
        $this->seedHotelCatalog();

        $costa = Property::query()->where('code', 'costa-azul')->firstOrFail();
        $sierra = Property::query()->where('code', 'sierra-verde')->firstOrFail();

        $recepcion = User::query()->where('email', 'recepcionista@gmail.com')->firstOrFail();
        $recepcion->properties()->sync([$costa->id => ['is_default' => true]]);

        Sanctum::actingAs($recepcion, ['*']);

        $this->withHeader('X-Property-Id', (string) $sierra->id)
            ->getJson('/api/hotel/room-types')
            ->assertForbidden();
    }

    public function test_demo_user_assignments_from_seed(): void
    {
        $this->seedHotelCatalog();
        $this->seed(\Database\Seeders\PropertyUserSeeder::class);

        $cajero = User::query()->where('email', 'cajero@gmail.com')->firstOrFail();
        Sanctum::actingAs($cajero, ['*']);

        $this->getJson('/api/hotel/properties')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'sierra-verde');

        $recepcion = User::query()->where('email', 'recepcionista@gmail.com')->firstOrFail();
        Sanctum::actingAs($recepcion, ['*']);

        $this->getJson('/api/hotel/properties')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}

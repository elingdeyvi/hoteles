<?php

namespace Tests\Feature\Hotel;

use App\Models\Property;
use Tests\HotelTestCase;

class PropertyManagementTest extends HotelTestCase
{
    public function test_admin_can_create_property(): void
    {
        $this->seedHotelCatalog();

        $this->actingAsAdmin()
            ->postJson('/api/hotel/properties', [
                'name' => 'Hotel Playa Norte',
                'code' => 'playa-norte',
                'currency' => 'mxn',
                'booking_enabled' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'playa-norte');

        $this->assertDatabaseHas('properties', ['code' => 'playa-norte']);
        $this->assertDatabaseHas('configuracion_empresa', [
            'property_id' => Property::query()->where('code', 'playa-norte')->value('id'),
            'nombre_empresa' => 'Hotel Playa Norte',
        ]);
    }

    public function test_admin_can_list_all_properties_for_management(): void
    {
        $this->seedHotelCatalog();

        $this->actingAsAdmin()
            ->getJson('/api/hotel/properties/manage')
            ->assertOk()
            ->assertJsonFragment(['code' => 'costa-azul'])
            ->assertJsonFragment(['code' => 'sierra-verde']);
    }

    public function test_cannot_deactivate_last_active_property(): void
    {
        $this->seedHotelCatalog();

        $sierra = Property::query()->where('code', 'sierra-verde')->firstOrFail();
        $costa = Property::query()->where('code', 'costa-azul')->firstOrFail();

        $this->actingAsAdmin()
            ->deleteJson("/api/hotel/properties/{$sierra->id}")
            ->assertOk();

        $this->actingAsAdmin()
            ->deleteJson("/api/hotel/properties/{$costa->id}")
            ->assertStatus(422);
    }
}

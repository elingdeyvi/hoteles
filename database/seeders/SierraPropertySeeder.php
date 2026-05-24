<?php

namespace Database\Seeders;

use App\Models\ConfiguracionEmpresa;
use App\Models\Property;
use Database\Seeders\Support\PropertyCatalog;
use Illuminate\Database\Seeder;

class SierraPropertySeeder extends Seeder
{
    public function run(): void
    {
        $property = Property::query()->firstOrCreate(
            ['code' => 'sierra-verde'],
            [
                'name' => 'Hotel Sierra Verde',
                'timezone' => 'America/Mexico_City',
                'currency' => 'MXN',
                'phone' => '555-0200',
                'email' => 'recepcion@sierraverde.demo',
                'address' => 'Carretera Montaña Km 12, Valle Alto',
                'booking_enabled' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        ConfiguracionEmpresa::query()->updateOrCreate(
            ['property_id' => $property->id],
            [
                'nombre_empresa' => 'Hotel Sierra Verde',
                'nombre_corto' => 'Sierra Verde',
                'nombre_largo' => 'Hotel Sierra Verde — Retiro en la montaña',
                'rfc' => 'HTL000001XXX',
                'telefono' => '555-0200',
                'email' => 'recepcion@sierraverde.demo',
                'direccion' => 'Carretera Montaña Km 12, Valle Alto',
                'color_primario' => '#1e4d3a',
                'color_secundario' => '#64748b',
            ]
        );

        if ($property->roomTypes()->exists()) {
            return;
        }

        PropertyCatalog::seedRoomTypes($property->id, [
            [
                'name' => 'Cabaña',
                'code' => 'cabana',
                'description' => 'Cabaña de madera con chimenea',
                'capacity' => 2,
                'amenities' => ['wifi', 'tv', 'chimenea', 'terraza'],
                'base_price' => 950,
            ],
            [
                'name' => 'Vista Montaña',
                'code' => 'vista-montana',
                'description' => 'Doble con balcón panorámico',
                'capacity' => 2,
                'amenities' => ['wifi', 'tv', 'aire_acondicionado', 'balcon'],
                'base_price' => 1350,
            ],
            [
                'name' => 'Loft Familiar',
                'code' => 'loft',
                'description' => 'Dos niveles, ideal para familias',
                'capacity' => 4,
                'amenities' => ['wifi', 'tv', 'cocina', 'terraza'],
                'base_price' => 2400,
            ],
        ]);

        if (! $property->posOutlets()->exists()) {
            PropertyCatalog::seedPos($property->id);
        }
    }
}

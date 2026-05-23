<?php

namespace Database\Seeders;

use App\Models\Huesped;
use App\Models\Property;
use Database\Seeders\Support\PropertyCatalog;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $propertyId = Property::query()->where('code', 'costa-azul')->value('id');
        if (! $propertyId) {
            return;
        }

        PropertyCatalog::seedRoomTypes($propertyId, [
            [
                'name' => 'Sencilla',
                'code' => 'sencilla',
                'description' => 'Cama individual, ideal para viajero solo',
                'capacity' => 1,
                'amenities' => ['wifi', 'tv', 'aire_acondicionado'],
                'base_price' => 850,
            ],
            [
                'name' => 'Doble',
                'code' => 'doble',
                'description' => 'Cama queen, vista al jardín',
                'capacity' => 2,
                'amenities' => ['wifi', 'tv', 'aire_acondicionado', 'minibar'],
                'base_price' => 1200,
            ],
            [
                'name' => 'Suite',
                'code' => 'suite',
                'description' => 'King size, vista al mar, sala de estar',
                'capacity' => 3,
                'amenities' => ['wifi', 'tv', 'aire_acondicionado', 'minibar', 'jacuzzi', 'vista_mar'],
                'base_price' => 2200,
            ],
        ]);

        Huesped::firstOrCreate(
            ['email' => 'huesped@demo.com'],
            [
                'nombre' => 'Huésped de demostración',
                'telefono' => '5551234567',
                'documento' => 'DEM-001',
                'nacionalidad' => 'MX',
            ]
        );
    }
}

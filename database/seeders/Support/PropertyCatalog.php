<?php

namespace Database\Seeders\Support;

use App\Models\PosCategory;
use App\Models\PosOutlet;
use App\Models\PosProduct;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\RoomType;

class PropertyCatalog
{
    /** @param list<array{name: string, code: string, description: string, capacity: int, amenities: list<string>, base_price: float}> $types */
    public static function seedRoomTypes(int $propertyId, array $types, int $roomsPerType = 5): void
    {
        foreach ($types as $index => $definition) {
            $type = RoomType::create([
                'property_id' => $propertyId,
                ...$definition,
            ]);

            RoomRate::create([
                'room_type_id' => $type->id,
                'name' => 'Tarifa estándar',
                'price' => $type->base_price,
                'is_weekend' => false,
            ]);
            RoomRate::create([
                'room_type_id' => $type->id,
                'name' => 'Fin de semana',
                'price' => round((float) $type->base_price * 1.25, 2),
                'is_weekend' => true,
            ]);

            for ($n = 1; $n <= $roomsPerType; $n++) {
                Room::create([
                    'property_id' => $propertyId,
                    'room_type_id' => $type->id,
                    'number' => chr(65 + $index).str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                    'floor' => $n <= 2 ? 1 : 2,
                    'status' => 'disponible',
                ]);
            }
        }
    }

    public static function seedPos(int $propertyId): void
    {
        $restaurante = PosOutlet::create(['property_id' => $propertyId, 'name' => 'Restaurante', 'code' => 'restaurante']);
        $bar = PosOutlet::create(['property_id' => $propertyId, 'name' => 'Bar', 'code' => 'bar']);
        $tienda = PosOutlet::create(['property_id' => $propertyId, 'name' => 'Tienda', 'code' => 'tienda']);

        $bebidas = PosCategory::create(['pos_outlet_id' => $bar->id, 'name' => 'Bebidas', 'sort_order' => 1]);
        $cocteles = PosCategory::create(['pos_outlet_id' => $bar->id, 'name' => 'Cócteles', 'sort_order' => 2]);
        $desayunos = PosCategory::create(['pos_outlet_id' => $restaurante->id, 'name' => 'Desayunos', 'sort_order' => 1]);
        $cenas = PosCategory::create(['pos_outlet_id' => $restaurante->id, 'name' => 'Cenas', 'sort_order' => 2]);
        $snacks = PosCategory::create(['pos_outlet_id' => $tienda->id, 'name' => 'Snacks', 'sort_order' => 1]);

        $products = [
            [$bebidas->id, 'Agua 500ml', 25],
            [$bebidas->id, 'Refresco', 35],
            [$bebidas->id, 'Cerveza nacional', 45],
            [$cocteles->id, 'Mojito', 95],
            [$cocteles->id, 'Margarita', 110],
            [$desayunos->id, 'Desayuno americano', 180],
            [$desayunos->id, 'Hot cakes', 120],
            [$cenas->id, 'Filete 300g', 320],
            [$cenas->id, 'Ensalada César', 145],
            [$snacks->id, 'Papas fritas', 55],
            [$snacks->id, 'Chocolate', 40],
        ];

        foreach ($products as [$catId, $name, $price]) {
            PosProduct::create([
                'pos_category_id' => $catId,
                'name' => $name,
                'price' => $price,
            ]);
        }
    }
}

<?php

namespace Database\Seeders\Support;

use App\Models\PosCategory;
use App\Models\PosOutlet;
use App\Models\PosProduct;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\SeasonRate;

class PropertyCatalog
{
    /** @param list<array{name: string, code: string, description: string, capacity: int, amenities: list<string>, base_price: float}> $types */
    public static function seedRoomTypes(int $propertyId, array $types, int $roomsPerType = 5): void
    {
        foreach ($types as $index => $definition) {
            $type = RoomType::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $propertyId, 'code' => $definition['code']],
                $definition
            );

            if (! $type->rates()->exists()) {
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
            }

            if ($type->rooms()->exists()) {
                continue;
            }

            for ($n = 1; $n <= $roomsPerType; $n++) {
                Room::withoutGlobalScopes()->create([
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
        if (PosOutlet::withoutGlobalScopes()->where('property_id', $propertyId)->exists()) {
            return;
        }

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

        foreach ($products as $index => [$catId, $name, $price]) {
            PosProduct::create([
                'pos_category_id' => $catId,
                'name' => $name,
                'sku' => 'POS-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'price' => $price,
            ]);
        }
    }

    public static function seedSeasons(int $propertyId): void
    {
        $year = (int) now()->year;
        $seasons = [
            ['season_name' => 'Temporada alta', 'starts_on' => "{$year}-07-01", 'ends_on' => "{$year}-08-31", 'multiplier' => 1.20, 'price_override' => null],
            ['season_name' => 'Puente de septiembre', 'starts_on' => "{$year}-09-14", 'ends_on' => "{$year}-09-16", 'multiplier' => 1.15, 'price_override' => null],
            ['season_name' => 'Fin de año', 'starts_on' => "{$year}-12-20", 'ends_on' => ($year + 1).'-01-06', 'multiplier' => 1.40, 'price_override' => null],
        ];

        $types = RoomType::withoutGlobalScopes()->where('property_id', $propertyId)->with('rates')->get();

        foreach ($types as $type) {
            foreach ($type->rates as $rate) {
                foreach ($seasons as $season) {
                    SeasonRate::query()->firstOrCreate(
                        [
                            'room_rate_id' => $rate->id,
                            'season_name' => $season['season_name'],
                        ],
                        $season
                    );
                }
            }
        }
    }
}

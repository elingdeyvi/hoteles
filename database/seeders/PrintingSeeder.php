<?php

namespace Database\Seeders;

use App\Models\Impresora;
use App\Models\Property;
use Illuminate\Database\Seeder;

class PrintingSeeder extends Seeder
{
    public function run(): void
    {
        Property::query()->orderBy('id')->each(function (Property $property): void {
            Impresora::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'nombre' => 'Recepción'],
                [
                    'puerto' => 9100,
                    'ancho_papel' => 80,
                    'activo' => true,
                    'es_default' => true,
                ]
            );
        });
    }
}

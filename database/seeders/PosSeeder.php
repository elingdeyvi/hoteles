<?php

namespace Database\Seeders;

use App\Models\Property;
use Database\Seeders\Support\PropertyCatalog;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $propertyId = Property::query()->where('code', 'costa-azul')->value('id');
        if (! $propertyId) {
            return;
        }

        PropertyCatalog::seedPos($propertyId);
    }
}

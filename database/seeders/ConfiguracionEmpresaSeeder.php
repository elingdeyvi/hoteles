<?php

namespace Database\Seeders;

use App\Models\ConfiguracionEmpresa;
use App\Models\Property;
use Illuminate\Database\Seeder;

class ConfiguracionEmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $propertyId = Property::query()->where('code', 'costa-azul')->value('id');

        ConfiguracionEmpresa::query()->firstOrCreate(
            ['property_id' => $propertyId],
            [
                'nombre_empresa' => 'Hotel Costa Azul',
            'nombre_corto' => 'Costa Azul',
            'nombre_largo' => 'Hotel Costa Azul — Sistema de gestión hotelera',
            'rfc' => 'HTL000000XXX',
            'telefono' => '555-0100',
            'email' => 'recepcion@costaazul.demo',
            'direccion' => 'Av. del Mar 100, Playa Norte',
        ]);
    }
}

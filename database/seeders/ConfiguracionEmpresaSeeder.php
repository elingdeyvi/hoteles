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

        ConfiguracionEmpresa::query()->updateOrCreate(
            ['property_id' => $propertyId],
            [
                'nombre_empresa' => 'Hotel Costa Azul',
                'nombre_corto' => 'Costa Azul',
                'nombre_largo' => 'Hotel Costa Azul — Frente al mar',
                'rfc' => 'HTL000000XXX',
                'descripcion' => 'Hotel de playa con restaurante, bar y tienda. Datos de demostración.',
                'telefono' => '555-0100',
                'email' => 'recepcion@costaazul.demo',
                'direccion' => 'Av. del Mar 100, Playa Norte',
                'codigo_postal' => '77500',
                'ciudad' => 'Cancún',
                'estado' => 'Quintana Roo',
                'pais' => 'México',
                'sitio_web' => 'https://costaazul.demo',
                'color_primario' => '#1a365d',
                'color_secundario' => '#64748b',
            ]);
    }
}

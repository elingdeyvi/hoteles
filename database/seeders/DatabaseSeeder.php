<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConfiguracionEmpresaSeeder::class,
            HotelSeeder::class,
            PosSeeder::class,
            SierraPropertySeeder::class,
            DemoUsersPasswordSeeder::class,
            PropertyUserSeeder::class,
            DemoOperationalDataSeeder::class,
        ]);
    }
}

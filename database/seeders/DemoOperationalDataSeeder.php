<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Database\Seeders\Support\DemoPropertyOperations;
use Illuminate\Database\Seeder;

class DemoOperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->first();
        if (! $admin) {
            return;
        }

        /** @var DemoPropertyOperations $operations */
        $operations = app(DemoPropertyOperations::class);

        $costa = Property::query()->where('code', 'costa-azul')->first();
        if ($costa && $costa->roomTypes()->exists()) {
            $operations->seed($costa, $admin, full: true);
        }

        $sierra = Property::query()->where('code', 'sierra-verde')->first();
        if ($sierra && $sierra->roomTypes()->exists()) {
            $operations->seed($sierra, $admin, full: false);
        }
    }
}

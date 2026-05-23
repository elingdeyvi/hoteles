<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertyUserSeeder extends Seeder
{
    public function run(): void
    {
        $costaId = Property::query()->where('code', 'costa-azul')->value('id');
        $sierraId = Property::query()->where('code', 'sierra-verde')->value('id');

        if (! $costaId) {
            return;
        }

        $recepcion = User::query()->where('email', 'recepcionista@gmail.com')->first();
        if ($recepcion) {
            $sync = [$costaId => ['is_default' => true]];
            if ($sierraId) {
                $sync[$sierraId] = ['is_default' => false];
            }
            $recepcion->properties()->sync($sync);
        }

        $housekeeping = User::query()->where('email', 'housekeeping@gmail.com')->first();
        if ($housekeeping) {
            $housekeeping->properties()->sync([
                $costaId => ['is_default' => true],
            ]);
        }

        $cajero = User::query()->where('email', 'cajero@gmail.com')->first();
        if ($cajero && $sierraId) {
            $cajero->properties()->sync([
                $sierraId => ['is_default' => true],
            ]);
        } elseif ($cajero) {
            $cajero->properties()->sync([
                $costaId => ['is_default' => true],
            ]);
        }
    }
}

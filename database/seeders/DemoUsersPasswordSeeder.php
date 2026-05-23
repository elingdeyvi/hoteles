<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersPasswordSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::query()
            ->whereIn('email', [
                'admin@gmail.com',
                'recepcionista@gmail.com',
                'housekeeping@gmail.com',
                'cajero@gmail.com',
            ])
            ->update(['password' => $password]);
    }
}

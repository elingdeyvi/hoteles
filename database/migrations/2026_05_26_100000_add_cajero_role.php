<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'pos.vender', 'guard_name' => 'web']);

        $cajero = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions(['pos.vender']);

        if (! User::where('email', 'cajero@gmail.com')->exists()) {
            $user = User::create([
                'name' => 'Cajero POS',
                'email' => 'cajero@gmail.com',
                'password' => '$2y$10$jOunoLNJ1KJ5MK3smYZ0q./c.PnQYnM4BAd0rikaRBAmGYveG8ydK',
                'email_verified_at' => now(),
            ]);
            $user->assignRole($cajero);
        }
    }

    public function down(): void
    {
        User::where('email', 'cajero@gmail.com')->delete();
        Role::where('name', 'Cajero')->where('guard_name', 'web')->delete();
    }
};

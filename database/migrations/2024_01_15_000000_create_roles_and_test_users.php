<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sistema de gestión hotelera — roles base: Administrador, Recepcionista, Housekeeping (Cajero en migración 2026_05_26)
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            'dashboard.ver',
            'administracion.usuarios',
            'administracion.roles',
            'hotel.configurar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $adminRole = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $recepcionistaRole = Role::create(['name' => 'Recepcionista', 'guard_name' => 'web']);
        $housekeepingRole = Role::create(['name' => 'Housekeeping', 'guard_name' => 'web']);

        // Asignar permisos al rol Administrador (todos los permisos base)
        $adminRole->givePermissionTo($permissions);

        // Permisos operativos del hotel en migración 2026_05_23_100000_create_hotel_management_schema.php

        // Usuarios de demostración
        // Nota: No se incluye 'estatus' aquí porque se agrega en una migración posterior (2024_01_15_000013)
        // El valor por defecto 'activo' se aplicará automáticamente cuando se agregue la columna
        $adminUser = User::create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => '$2y$10$jOunoLNJ1KJ5MK3smYZ0q./c.PnQYnM4BAd0rikaRBAmGYveG8ydK',
            'email_verified_at' => now(),
        ]);

        $recepcionistaUser = User::create([
            'name' => 'Recepcionista',
            'email' => 'recepcionista@gmail.com',
            'password' => '$2y$10$jOunoLNJ1KJ5MK3smYZ0q./c.PnQYnM4BAd0rikaRBAmGYveG8ydK',
            'email_verified_at' => now(),
        ]);

        $housekeepingUser = User::create([
            'name' => 'Housekeeping',
            'email' => 'housekeeping@gmail.com',
            'password' => '$2y$10$jOunoLNJ1KJ5MK3smYZ0q./c.PnQYnM4BAd0rikaRBAmGYveG8ydK',
            'email_verified_at' => now(),
        ]);

        $adminUser->assignRole($adminRole);
        $recepcionistaUser->assignRole($recepcionistaRole);
        $housekeepingUser->assignRole($housekeepingRole);
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina roles y usuarios demo del sistema hotelero.
     *
     * @return void
     */
    public function down()
    {
        // Usuarios demo del MVP hotelero
        User::where('email', 'admin@gmail.com')->delete();
        User::where('email', 'recepcionista@gmail.com')->delete();
        User::where('email', 'housekeeping@gmail.com')->delete();

        Role::where('name', 'Administrador')->delete();
        Role::where('name', 'Recepcionista')->delete();
        Role::where('name', 'Housekeeping')->delete();

        // Eliminar permisos
        $permissions = [
            'dashboard.ver',
            'administracion.usuarios',
            'administracion.roles',
            'hotel.configurar',
        ];
        
        Permission::whereIn('name', $permissions)->delete();
    }
};

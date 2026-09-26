<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'dashboard.ver',
        'administracion.usuarios',
        'administracion.roles',
        'hotel.configurar',
        'recepcion.reservas',
        'recepcion.checkin',
        'recepcion.checkout',
        'recepcion.huespedes',
        'facturacion.folios',
        'facturacion.pagos',
        'housekeeping.gestionar',
        'reportes.ver',
        'pos.vender',
        'pos.catalogo',
        'caja.operar',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => User::ADMIN_ROL, 'guard_name' => 'web']);
        $recepcion = Role::firstOrCreate(['name' => User::RECEPCIONISTA_ROL, 'guard_name' => 'web']);
        $housekeeping = Role::firstOrCreate(['name' => User::HOUSEKEEPING_ROL, 'guard_name' => 'web']);
        $cajero = Role::firstOrCreate(['name' => User::CAJERO_ROL, 'guard_name' => 'web']);

        $admin->syncPermissions(self::PERMISSIONS);
        $recepcion->syncPermissions([
            'dashboard.ver',
            'recepcion.reservas',
            'recepcion.checkin',
            'recepcion.checkout',
            'recepcion.huespedes',
            'facturacion.folios',
            'facturacion.pagos',
            'reportes.ver',
            'pos.vender',
            'caja.operar',
        ]);
        $housekeeping->syncPermissions([
            'dashboard.ver',
            'housekeeping.gestionar',
        ]);
        $cajero->syncPermissions(['pos.vender', 'caja.operar']);

        $this->user('Administrador', 'admin@gmail.com', User::ADMIN_ROL);
        $this->user('Recepcionista', 'recepcionista@gmail.com', User::RECEPCIONISTA_ROL);
        $this->user('Housekeeping', 'housekeeping@gmail.com', User::HOUSEKEEPING_ROL);
        $this->user('Cajero POS', 'cajero@gmail.com', User::CAJERO_ROL);
    }

    private function user(string $name, string $email, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => '12345678',
                'estatus' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);
    }
}

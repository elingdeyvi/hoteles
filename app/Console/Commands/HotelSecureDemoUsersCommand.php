<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class HotelSecureDemoUsersCommand extends Command
{
    protected $signature = 'hotel:secure-demo-users {--force : Ejecutar sin confirmación}';

    protected $description = 'Desactiva usuarios demo (admin@gmail.com, etc.) antes de producción';

    public function handle(): int
    {
        $emails = config('hotel.demo_user_emails', []);
        if ($emails === []) {
            $this->warn('No hay emails demo configurados.');

            return self::SUCCESS;
        }

        $users = User::query()
            ->whereIn('email', $emails)
            ->where('estatus', 'activo')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No hay usuarios demo activos.');

            return self::SUCCESS;
        }

        $this->table(['Email', 'Nombre'], $users->map(fn (User $u) => [$u->email, $u->name])->all());

        if (! $this->option('force') && ! $this->confirm('¿Desactivar estos usuarios demo?', true)) {
            $this->warn('Operación cancelada.');

            return self::FAILURE;
        }

        $count = User::query()
            ->whereIn('email', $emails)
            ->where('estatus', 'activo')
            ->update(['estatus' => 'inactivo']);

        $this->info("{$count} usuario(s) demo desactivado(s).");
        $this->line('Cree usuarios reales en el panel antes de abrir el sistema al público.');

        return self::SUCCESS;
    }
}

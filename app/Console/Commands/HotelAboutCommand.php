<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class HotelAboutCommand extends Command
{
    protected $signature = 'hotel:about';

    protected $description = 'Resumen del sistema hotelero instalado';

    public function handle(): int
    {
        $db = 'error';
        try {
            DB::connection()->getPdo();
            $db = 'ok';
        } catch (\Throwable) {
            //
        }

        $this->info('Sistema de gestión hotelera');
        $this->line('Versión: '.config('hotel.health.version', '1.0.0'));
        $this->line('Entorno: '.app()->environment());
        $this->line('Base de datos: '.$db);
        $this->newLine();

        $this->comment('Módulos MVP');
        $rows = [
            ['Configuración', 'hotel.configurar', '/hotel/config/*'],
            ['Recepción', 'recepcion.*', '/hotel/recepcion/*'],
            ['POS consumos', 'pos.vender', '/hotel/pos'],
            ['Facturación', 'facturacion.*', '/hotel/facturacion/folios'],
            ['Housekeeping', 'housekeeping.gestionar', '/hotel/housekeeping'],
            ['Reportes', 'reportes.ver', '/hotel/reportes'],
            ['Booking web', '(público)', '/reservar'],
            ['Health', '(público)', '/api/health'],
        ];
        $this->table(['Módulo', 'Permiso', 'Ruta'], $rows);

        $this->newLine();
        $this->comment('Hoteles demo (tras db:seed)');
        if ($db === 'ok') {
            $properties = Property::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['code', 'name', 'booking_enabled']);
            if ($properties->isEmpty()) {
                $this->line('  (ninguno — ejecute php artisan migrate --seed)');
            } else {
                $this->table(
                    ['Código', 'Nombre', 'Booking web'],
                    $properties->map(fn (Property $p) => [
                        $p->code,
                        $p->name,
                        $p->booking_enabled ? 'sí' : 'no',
                    ])->all()
                );
            }
        }

        $this->newLine();
        $this->comment('Usuarios demo (contraseña: 12345678 tras db:seed)');
        $this->table(['Email', 'Rol'], [
            ['admin@gmail.com', 'Administrador'],
            ['recepcionista@gmail.com', 'Recepcionista'],
            ['housekeeping@gmail.com', 'Housekeeping'],
            ['cajero@gmail.com', 'Cajero'],
        ]);
        $this->line('  En producción: php artisan hotel:secure-demo-users --force');

        return self::SUCCESS;
    }
}

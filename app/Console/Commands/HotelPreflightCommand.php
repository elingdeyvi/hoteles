<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HotelPreflightCommand extends Command
{
    protected $signature = 'hotel:preflight';

    protected $description = 'Verifica requisitos antes de desplegar a producción';

    public function handle(): int
    {
        $env = app()->environment();
        $isProd = $env === 'production';
        $isProdOrStaging = in_array($env, ['production', 'staging'], true);
        $checks = [];

        $this->info('Preflight — gestión hotelera');
        $this->line('Entorno: '.$env);
        $this->newLine();

        $checks[] = $this->check('APP_KEY definida', config('app.key') !== '' && config('app.key') !== null);

        if ($isProd) {
            $checks[] = $this->check('APP_DEBUG=false', config('app.debug') === false);
            $checks[] = $this->check('ALLOW_DEV_SETUP_ROUTES=false', ! config('app.allow_dev_setup_routes', false));
        }

        $checks[] = $this->check('storage/ escribible', is_writable(storage_path()));
        $checks[] = $this->check('bootstrap/cache/ escribible', is_writable(base_path('bootstrap/cache')));

        $manifest = public_path('build/manifest.json');
        $manifestOk = is_file($manifest);
        if ($isProdOrStaging) {
            $checks[] = $this->check('Assets Vite (public/build/manifest.json)', $manifestOk);
        } elseif (! $manifestOk) {
            $this->line('  <fg=yellow>!</> Assets Vite — omitido en '.$env.' (ejecuta npm run build antes de producción)');
        }

        if (config('hotel.booking.payments.enabled') && config('hotel.booking.payments.provider') === 'stripe') {
            $checks[] = $this->check('STRIPE_SECRET', (bool) config('hotel.stripe.secret'));
            $checks[] = $this->check('STRIPE_WEBHOOK_SECRET', (bool) config('hotel.stripe.webhook_secret'));
        }

        if ($isProd) {
            $provider = config('hotel.booking.payments.provider', 'demo');
            $checks[] = $this->check(
                'Proveedor de pago no-demo',
                $provider !== 'demo',
                'Use HOTEL_BOOKING_PAYMENT_PROVIDER=stripe o deshabilite pagos'
            );
        }

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('Conexión a base de datos', true);

            if (Schema::hasTable('properties')) {
                $activeProperties = DB::table('properties')->where('is_active', true)->count();
                $checks[] = $this->check('Al menos un hotel activo', $activeProperties >= 1);
            }

            if ($isProd && Schema::hasTable('users')) {
                $demoActive = User::query()
                    ->whereIn('email', config('hotel.demo_user_emails', []))
                    ->where('estatus', 'activo')
                    ->exists();
                $checks[] = $this->check(
                    'Usuarios demo desactivados',
                    ! $demoActive,
                    'Ejecute: php artisan hotel:secure-demo-users --force'
                );
            }
        } catch (\Throwable $e) {
            $checks[] = $this->check('Conexión a base de datos', false, $e->getMessage());
        }

        if ($isProd && config('mail.default') === 'log') {
            $checks[] = $this->check('MAIL (no usar driver log en prod)', false, 'Configura SMTP para reservas web');
        } elseif ($isProd) {
            $checks[] = $this->check('MAIL configurado', config('mail.mailers.'.config('mail.default').'.host') !== null
                || config('mail.default') === 'array');
        }

        $this->newLine();
        $failed = count(array_filter($checks, fn ($c) => ! $c['pass']));
        if ($failed > 0) {
            $this->error("{$failed} comprobación(es) fallaron.");

            return self::FAILURE;
        }

        $this->info('Listo para producción.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $pass, ?string $hint = null): array
    {
        if ($pass) {
            $this->line("  <fg=green>✓</> {$label}");
        } else {
            $msg = $hint ? " — {$hint}" : '';
            $this->line("  <fg=red>✗</> {$label}{$msg}");
        }

        return ['pass' => $pass];
    }
}

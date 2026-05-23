<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HotelStripeCheckCommand extends Command
{
    protected $signature = 'hotel:stripe-check';

    protected $description = 'Verifica configuración de Stripe para pagos en booking web';

    public function handle(): int
    {
        $provider = config('hotel.booking.payments.provider');
        $enabled = config('hotel.booking.payments.enabled');
        $secret = config('hotel.stripe.secret');
        $webhook = config('hotel.stripe.webhook_secret');

        $this->info('Stripe — booking web');
        $this->line('Pagos habilitados: '.($enabled ? 'sí' : 'no'));
        $this->line('Proveedor: '.$provider);
        $this->newLine();

        if (! $enabled || $provider !== 'stripe') {
            $this->warn('Stripe no está activo (HOTEL_BOOKING_PAYMENT_PROVIDER=stripe).');

            return self::SUCCESS;
        }

        $ok = true;

        if (! $secret) {
            $this->error('✗ STRIPE_SECRET no configurado');
            $ok = false;
        } else {
            $mode = Str::startsWith($secret, 'sk_live_') ? 'LIVE' : (Str::startsWith($secret, 'sk_test_') ? 'TEST' : 'desconocido');
            $this->line('✓ STRIPE_SECRET presente ('.Str::substr($secret, 0, 8).'…) — modo '.$mode);
            if (app()->environment('staging') && $mode === 'LIVE') {
                $this->warn('! En staging use claves sk_test_…');
            }
            if (app()->environment('production') && $mode === 'TEST') {
                $this->warn('! En producción use claves sk_live_…');
            }
        }

        if (! $webhook) {
            $this->warn('! STRIPE_WEBHOOK_SECRET vacío — los pagos no se confirmarán solos');
            $ok = false;
        } else {
            $this->line('✓ STRIPE_WEBHOOK_SECRET presente');
        }

        $appUrl = rtrim(config('app.url'), '/');
        $this->line('Webhook URL: '.$appUrl.'/api/booking/webhooks/stripe');
        $this->line('Success URL base: '.$appUrl.'/reservar/{codigo}?payment=success');

        if ($secret) {
            $response = Http::withBasicAuth($secret, '')->get('https://api.stripe.com/v1/balance');
            if ($response->successful()) {
                $this->line('✓ Conexión API Stripe OK');
            } else {
                $this->error('✗ STRIPE_SECRET inválido o sin conexión');
                $ok = false;
            }
        }

        $this->newLine();

        if (! $ok) {
            $this->error('Revisa docs/PAGOS-STRIPE.md');

            return self::FAILURE;
        }

        $this->info('Stripe listo para pruebas/producción.');

        return self::SUCCESS;
    }
}

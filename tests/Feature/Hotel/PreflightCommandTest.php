<?php

namespace Tests\Feature\Hotel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\HotelTestCase;

class PreflightCommandTest extends HotelTestCase
{
    use RefreshDatabase;

    public function test_preflight_passes_in_testing_environment(): void
    {
        $this->artisan('hotel:preflight')
            ->assertSuccessful();
    }

    public function test_preflight_fails_in_production_with_active_demo_users(): void
    {
        $this->prepareProductionPreflight();

        $this->artisan('hotel:preflight')
            ->assertFailed();
    }

    public function test_preflight_passes_in_production_after_securing_demo_users(): void
    {
        $this->prepareProductionPreflight();

        User::query()
            ->whereIn('email', config('hotel.demo_user_emails'))
            ->update(['estatus' => 'inactivo']);

        $this->artisan('hotel:preflight')
            ->assertSuccessful();
    }

    private function prepareProductionPreflight(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('app.debug', false);
        Config::set('app.allow_dev_setup_routes', false);
        Config::set('hotel.booking.payments.provider', 'stripe');
        Config::set('mail.default', 'array');

        $buildDir = public_path('build');
        if (! is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }
        if (! is_file(public_path('build/manifest.json'))) {
            file_put_contents(public_path('build/manifest.json'), '{}');
        }
    }
}

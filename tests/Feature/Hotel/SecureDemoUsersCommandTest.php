<?php

namespace Tests\Feature\Hotel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\HotelTestCase;

class SecureDemoUsersCommandTest extends HotelTestCase
{
    use RefreshDatabase;

    public function test_secure_demo_users_deactivates_accounts(): void
    {
        $this->assertTrue(
            User::query()->where('email', 'admin@gmail.com')->where('estatus', 'activo')->exists()
        );

        $this->artisan('hotel:secure-demo-users --force')
            ->assertSuccessful();

        $this->assertFalse(
            User::query()->whereIn('email', config('hotel.demo_user_emails'))->where('estatus', 'activo')->exists()
        );
    }

    public function test_secure_demo_users_is_idempotent(): void
    {
        $this->artisan('hotel:secure-demo-users --force')->assertSuccessful();
        $this->artisan('hotel:secure-demo-users --force')
            ->assertSuccessful()
            ->expectsOutput('No hay usuarios demo activos.');
    }
}

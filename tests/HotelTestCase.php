<?php

namespace Tests;

use App\Models\User;
use App\Support\CurrentProperty;
use Database\Seeders\HotelSeeder;
use Database\Seeders\PosSeeder;
use Database\Seeders\SierraPropertySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class HotelTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        Sanctum::actingAs($this->admin, ['*']);
    }

    protected function tearDown(): void
    {
        CurrentProperty::clear();

        parent::tearDown();
    }

    protected function seedHotelCatalog(): void
    {
        $this->seed(HotelSeeder::class);
        $this->seed(PosSeeder::class);
        $this->seed(SierraPropertySeeder::class);
    }

    protected function actingAsAdmin(): static
    {
        Sanctum::actingAs($this->admin, ['*']);

        return $this;
    }
}

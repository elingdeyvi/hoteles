<?php

namespace Tests\Feature\Hotel;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('database', 'ok')
            ->assertJsonStructure(['app', 'environment', 'time']);
    }
}

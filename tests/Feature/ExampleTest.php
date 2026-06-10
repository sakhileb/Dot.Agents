<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checks' => ['database', 'cache', 'storage', 'redis', 'queue'],
                'timestamp',
                'version',
            ])
            ->assertJsonPath('status', 'healthy');
    }

    public function test_health_endpoint_is_publicly_accessible_without_auth(): void
    {
        $response = $this->getJson('/api/health');

        // Must not redirect to login (401/302)
        $response->assertStatus(200);
    }

    public function test_health_endpoint_database_check_passes(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJsonPath('checks.database', 'ok');
    }

    public function test_health_endpoint_cache_check_passes(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJsonPath('checks.cache', 'ok');
    }

    public function test_health_endpoint_storage_check_passes(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJsonPath('checks.storage', 'ok');
    }
}

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

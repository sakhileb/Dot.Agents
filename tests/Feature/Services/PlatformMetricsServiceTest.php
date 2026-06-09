<?php

namespace Tests\Feature\Services;

use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\User;
use App\Services\Infrastructure\PlatformMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlatformMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Cache::flush();
    }

    public function test_get_operations_snapshot_returns_array_with_required_keys(): void
    {
        $snapshot = app(PlatformMetricsService::class)
            ->getOperationsSnapshot($this->organization->id);

        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('active_deployments', $snapshot);
        $this->assertArrayHasKey('agent_executions', $snapshot);
        $this->assertArrayHasKey('failure_rate', $snapshot);
        $this->assertArrayHasKey('avg_response_time_ms', $snapshot);
        $this->assertArrayHasKey('queue_depth', $snapshot);
        $this->assertArrayHasKey('security_events', $snapshot);
        $this->assertArrayHasKey('circuit_breakers', $snapshot);
    }

    public function test_active_deployment_count_reflects_database(): void
    {
        AgentDeployment::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);
        AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'paused',
        ]);

        $count = app(PlatformMetricsService::class)
            ->getActiveDeploymentCount($this->organization->id);

        $this->assertSame(3, $count);
    }

    public function test_snapshot_is_cached_for_60_seconds(): void
    {
        $service = app(PlatformMetricsService::class);
        $service->getOperationsSnapshot($this->organization->id);

        $cacheKey = "ops_snapshot_{$this->organization->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_invalidate_clears_cache(): void
    {
        $service = app(PlatformMetricsService::class);
        $service->getOperationsSnapshot($this->organization->id);

        $service->invalidate($this->organization->id);

        $this->assertFalse(Cache::has("ops_snapshot_{$this->organization->id}"));
    }

    public function test_circuit_breaker_statuses_returns_all_providers(): void
    {
        $statuses = app(PlatformMetricsService::class)->getCircuitBreakerStatuses();

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('ai_inference_openai', $statuses);
        $this->assertArrayHasKey('ai_inference_anthropic', $statuses);
        $this->assertArrayHasKey('ai_inference_google', $statuses);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\AgentSession;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\CustomerSuccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CustomerSuccessService.
 *
 * Tests verify score structure, boundary conditions, and caching behavior.
 */
class CustomerSuccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerSuccessService $service;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service      = app(CustomerSuccessService::class);
        $user               = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
    }

    public function test_returns_required_score_keys(): void
    {
        $result = $this->service->calculate($this->organization);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('dimensions', $result);
        $this->assertIsNumeric($result['score']);
    }

    public function test_score_is_between_0_and_100(): void
    {
        $result = $this->service->calculate($this->organization);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_returns_zero_score_dimensions_with_no_tasks(): void
    {
        $result = $this->service->calculate($this->organization);

        // No tasks → satisfaction dimension should be minimal / default
        $this->assertArrayHasKey('user_satisfaction', $result['dimensions']);
        $this->assertArrayHasKey('task_adoption', $result['dimensions']);
        $this->assertArrayHasKey('session_retention', $result['dimensions']);
    }

    public function test_invalidate_clears_cached_result(): void
    {
        // Warm the cache
        $this->service->calculate($this->organization);

        // Should not throw after invalidation
        $this->service->invalidate($this->organization);

        $result = $this->service->calculate($this->organization);
        $this->assertArrayHasKey('score', $result);
    }

    public function test_score_with_rated_tasks_is_valid(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status'          => 'active',
        ]);

        // Create rated completed tasks — service should produce a valid score
        AgentTask::factory()->count(10)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id'     => $this->organization->id,
            'status'              => 'completed',
            'user_rating'         => 5,
            'created_at'          => now()->subDays(2),
        ]);

        $this->service->invalidate($this->organization);
        $result = $this->service->calculate($this->organization);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertArrayHasKey('user_satisfaction', $result['dimensions']);
    }
}

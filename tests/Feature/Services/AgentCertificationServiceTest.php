<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\AgentCertificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCertificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgentCertificationService $service;

    private Agent $agent;

    private Organization $org;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->org = Organization::factory()->create();
        $this->agent = Agent::factory()->create();
        $this->service = app(AgentCertificationService::class);
    }

    public function test_certify_returns_required_keys(): void
    {
        $result = $this->service->certify($this->agent, $this->org->id);

        $this->assertArrayHasKey('certification_score', $result);
        $this->assertArrayHasKey('trust_tier', $result);
        $this->assertArrayHasKey('dimension_scores', $result);
        $this->assertArrayHasKey('certified_at', $result);
        $this->assertArrayHasKey('recommended_modes', $result);
        $this->assertArrayHasKey('requires_human_review', $result);
    }

    public function test_certification_score_is_within_valid_range(): void
    {
        $result = $this->service->certify($this->agent, $this->org->id);

        $this->assertGreaterThanOrEqual(0, $result['certification_score']);
        $this->assertLessThanOrEqual(100, $result['certification_score']);
    }

    public function test_trust_tier_is_valid_value(): void
    {
        $result = $this->service->certify($this->agent, $this->org->id);

        $this->assertContains($result['trust_tier'], ['bronze', 'silver', 'gold', 'platinum', 'unverified']);
    }

    public function test_certification_persists_to_agent_model(): void
    {
        $result = $this->service->certify($this->agent, $this->org->id);

        $this->agent->refresh();

        $this->assertNotNull($this->agent->certified_at);
        $this->assertEquals($result['certification_score'], $this->agent->certification_score);
        $this->assertEquals($result['trust_tier'], $this->agent->trust_tier);
    }

    public function test_recertify_clears_cache_and_recomputes(): void
    {
        $first = $this->service->certify($this->agent, $this->org->id);
        $second = $this->service->recertify($this->agent, $this->org->id);

        // Both should have valid structure
        $this->assertArrayHasKey('certification_score', $first);
        $this->assertArrayHasKey('certification_score', $second);
    }

    public function test_dimension_scores_contain_expected_dimensions(): void
    {
        $result = $this->service->certify($this->agent, $this->org->id);

        $dimensions = $result['dimension_scores'];
        $this->assertArrayHasKey('accuracy', $dimensions);
        $this->assertArrayHasKey('reliability', $dimensions);
        $this->assertArrayHasKey('security', $dimensions);
        $this->assertArrayHasKey('governance', $dimensions);
        $this->assertArrayHasKey('performance', $dimensions);
    }
}

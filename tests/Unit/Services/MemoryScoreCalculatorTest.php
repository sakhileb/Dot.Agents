<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\Memory\MemoryScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MemoryScoreCalculator.
 *
 * Tests verify score structure, dimension completeness, and boundary conditions.
 */
class MemoryScoreCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private MemoryScoreCalculator $calculator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new MemoryScoreCalculator;
        $user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
    }

    public function test_compute_returns_required_keys(): void
    {
        $result = $this->calculator->compute($this->organization->id);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('dimensions', $result);
        $this->assertIsNumeric($result['score']);
    }

    public function test_score_is_within_valid_range(): void
    {
        $result = $this->calculator->compute($this->organization->id);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_compute_handles_empty_organization(): void
    {
        // Organization with no memories/articles/decisions should not throw
        $result = $this->calculator->compute($this->organization->id);

        $this->assertArrayHasKey('score', $result);
    }

    public function test_dimensions_contain_all_five_areas(): void
    {
        $result = $this->calculator->compute($this->organization->id);
        $dimensions = $result['dimensions'];

        // All 5 scoring dimensions should be present
        $this->assertArrayHasKey('retention', $dimensions);
        $this->assertArrayHasKey('quality', $dimensions);
        $this->assertArrayHasKey('history', $dimensions);
        $this->assertArrayHasKey('rootCause', $dimensions);
        $this->assertArrayHasKey('velocity', $dimensions);
    }

    public function test_build_knowledge_graph_summary_returns_array(): void
    {
        $result = $this->calculator->buildKnowledgeGraphSummary($this->organization->id);

        $this->assertIsArray($result);
    }

    public function test_score_with_decision_logs_is_valid(): void
    {
        $deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        DecisionLog::factory()->count(5)->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->organization->id,
            'reality_alignment_score' => 95.0,
        ]);

        $result = $this->calculator->compute($this->organization->id);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }
}

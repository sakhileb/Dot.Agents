<?php

namespace Tests\Feature\Actions\Skills;

use App\Actions\Skills\RecordSkillScoreAction;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillScore;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordSkillScoreActionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private AgentDeployment $deployment;

    private AgentSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $user->id]);
        $this->deployment = AgentDeployment::factory()->create(['organization_id' => $this->organization->id]);
        $this->skill = AgentSkill::factory()->create();
        Gate::before(fn () => true);
    }

    #[Test]
    public function test_creates_skill_score_record(): void
    {
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'completed',
            confidence: 85.0,
            durationMs: 1200,
        );

        $score = AgentSkillScore::where('skill_id', $this->skill->id)
            ->where('agent_deployment_id', $this->deployment->id)
            ->first();

        $this->assertNotNull($score);
        $this->assertEquals(1, $score->total_executions);
        $this->assertEquals(1, $score->successful_executions);
        $this->assertEquals(100.0, $score->success_rate);
    }

    #[Test]
    public function test_increments_failed_executions(): void
    {
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'failed',
        );

        $score = AgentSkillScore::where('skill_id', $this->skill->id)->first();
        $this->assertEquals(1, $score->failed_executions);
        $this->assertEquals(0.0, $score->success_rate);
    }

    #[Test]
    public function test_accumulates_multiple_executions(): void
    {
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'completed',
        );
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'completed',
        );
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'failed',
        );

        $score = AgentSkillScore::where('skill_id', $this->skill->id)->first();
        $this->assertEquals(3, $score->total_executions);
        $this->assertEquals(2, $score->successful_executions);
        $this->assertEquals(1, $score->failed_executions);
    }
}

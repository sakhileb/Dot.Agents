<?php

namespace Tests\Feature\Actions\Skills;

use App\Actions\Skills\ProcessSkillApprovalAction;
use App\Events\SkillExecuted;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillApproval;
use App\Models\AgentSkillExecution;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProcessSkillApprovalActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentDeployment $deployment;

    private AgentSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->deployment = AgentDeployment::factory()->create(['organization_id' => $this->organization->id]);
        $this->skill = AgentSkill::factory()->create(['approval_required' => true]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
    }

    #[Test]
    public function test_approves_skill_approval(): void
    {
        Event::fake([SkillExecuted::class]);

        $execution = AgentSkillExecution::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'execution_id' => $execution->id,
            'status' => 'pending',
        ]);

        $result = app(ProcessSkillApprovalAction::class)->execute(
            $approval,
            'approved',
            $this->user->id,
            'Looks good to proceed',
        );

        $this->assertEquals('approved', $result->status);
        $this->assertEquals($this->user->id, $result->reviewed_by);
        $this->assertNotNull($result->reviewed_at);
        $this->assertEquals('running', $execution->fresh()->status);
        Event::assertDispatched(SkillExecuted::class);
    }

    #[Test]
    public function test_rejects_skill_approval(): void
    {
        Event::fake();

        $execution = AgentSkillExecution::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'execution_id' => $execution->id,
            'status' => 'pending',
        ]);

        $result = app(ProcessSkillApprovalAction::class)->execute(
            $approval,
            'rejected',
            $this->user->id,
            'Too risky',
        );

        $this->assertEquals('rejected', $result->status);
        $this->assertEquals('skipped', $execution->fresh()->status);
    }

    #[Test]
    public function test_aborts_on_already_processed_approval(): void
    {
        Event::fake();

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'approved',
        ]);

        $this->expectException(HttpException::class);
        app(ProcessSkillApprovalAction::class)->execute($approval, 'approved', $this->user->id);
    }
}

<?php

namespace Tests\Feature\Actions\Skills;

use App\Actions\Skills\AssignSkillToDeploymentAction;
use App\Actions\Skills\ExecuteSkillAction;
use App\Actions\Skills\ProcessSkillApprovalAction;
use App\Actions\Skills\RecordSkillScoreAction;
use App\DTOs\Skills\AssignSkillData;
use App\DTOs\Skills\ExecuteSkillData;
use App\Events\SkillApprovalRequested;
use App\Events\SkillExecuted;
use App\Events\SkillExecutionBlocked;
use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillApproval;
use App\Models\AgentSkillAssignment;
use App\Models\AgentSkillAudit;
use App\Models\AgentSkillExecution;
use App\Models\AgentSkillScore;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Comprehensive test suite for all four Skill Action classes.
 *
 * Covers:
 *  1. Skill Authorization
 *  2. Skill Governance (policy validation, risk levels)
 *  3. Skill Approval Workflow
 *  4. Skill Audit Logging
 *  5. Skill Scoring
 *  6. Skill Failure Recovery
 *  7. Skill Delegation
 *  8. Skill Tenant Isolation
 */
class SkillActionTestSuite extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentDeployment $deployment;

    private AgentSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->create();
        $this->organization->users()->attach($this->user, ['role' => 'admin']);

        // Authenticate the user and bypass all Gate policies so tests focus on
        // action behavior rather than authorization mechanics.
        $this->actingAs($this->user);
        Gate::before(fn () => true);

        $agent = Agent::factory()->create();

        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_id' => $agent->id,
            'status' => 'active',
        ]);

        $this->skill = AgentSkill::factory()->create([
            'is_active' => true,
            'approval_required' => false,
            'audit_required' => true,
            'risk_level' => 'low',
            'confidence_score' => 80.0,
        ]);
    }

    // 1. Skill Authorization

    /** @test */
    public function assign_skill_creates_assignment_for_active_skill(): void
    {
        $data = new AssignSkillData(
            skillId: $this->skill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            isEnabled: true,
            config: ['max_retries' => 3],
        );

        $assignment = app(AssignSkillToDeploymentAction::class)->execute($data);

        $this->assertInstanceOf(AgentSkillAssignment::class, $assignment);
        $this->assertEquals($this->skill->id, $assignment->skill_id);
        $this->assertTrue($assignment->is_enabled);
        $this->assertDatabaseHas('agent_skill_assignments', [
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
        ]);
    }

    // 2. Skill Governance

    /** @test */
    public function assign_skill_is_idempotent_on_second_call(): void
    {
        $data = new AssignSkillData(
            skillId: $this->skill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
        );

        app(AssignSkillToDeploymentAction::class)->execute($data);
        app(AssignSkillToDeploymentAction::class)->execute($data);

        $this->assertEquals(1, AgentSkillAssignment::withoutGlobalScopes()
            ->where('skill_id', $this->skill->id)
            ->where('agent_deployment_id', $this->deployment->id)
            ->count());
    }

    /** @test */
    public function assign_skill_rejects_inactive_skill(): void
    {
        $inactiveSkill = AgentSkill::factory()->create(['is_active' => false]);

        $data = new AssignSkillData(
            skillId: $inactiveSkill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
        );

        $this->expectException(HttpException::class);

        app(AssignSkillToDeploymentAction::class)->execute($data);
    }

    // 3. Skill Approval Workflow

    /** @test */
    public function execute_skill_with_approval_required_creates_pending_approval(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        $approvalSkill = AgentSkill::factory()->create([
            'is_active' => true,
            'approval_required' => true,
            'audit_required' => false,
            'risk_level' => 'high',
        ]);

        AgentSkillAssignment::factory()->create([
            'skill_id' => $approvalSkill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'is_enabled' => true,
        ]);

        $data = new ExecuteSkillData(
            skillId: $approvalSkill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'manual',
        );

        $execution = app(ExecuteSkillAction::class)->execute($data);

        $this->assertEquals('pending', $execution->status);
        $this->assertDatabaseHas('agent_skill_approvals', [
            'skill_id' => $approvalSkill->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'pending',
        ]);
        Event::assertDispatched(SkillApprovalRequested::class);
    }

    /** @test */
    public function process_approval_approved_advances_execution_to_running(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        $execution = AgentSkillExecution::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'execution_id' => $execution->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        app(ProcessSkillApprovalAction::class)->execute($approval, 'approved', $this->user->id, 'Looks good');

        $this->assertEquals('approved', $approval->fresh()->status);
        $this->assertEquals('running', $execution->fresh()->status);
        Event::assertDispatched(SkillExecuted::class);
    }

    /** @test */
    public function process_approval_rejected_marks_execution_as_skipped(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        $execution = AgentSkillExecution::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'execution_id' => $execution->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        app(ProcessSkillApprovalAction::class)->execute($approval, 'rejected', $this->user->id, 'Not authorized');

        $this->assertEquals('rejected', $approval->fresh()->status);
        $this->assertEquals('skipped', $execution->fresh()->status);
    }

    /** @test */
    public function process_approval_rejects_already_processed_approval(): void
    {
        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'approved',
            'expires_at' => now()->addDay(),
        ]);

        $this->expectException(HttpException::class);

        app(ProcessSkillApprovalAction::class)->execute($approval, 'approved', $this->user->id);
    }

    // 4. Skill Audit Logging

    /** @test */
    public function execute_skill_creates_audit_trail_when_audit_required(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        AgentSkillAssignment::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'is_enabled' => true,
        ]);

        $data = new ExecuteSkillData(
            skillId: $this->skill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'scheduled',
        );

        app(ExecuteSkillAction::class)->execute($data);

        $this->assertDatabaseHas('agent_skill_audits', [
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'event_type' => AgentSkillAudit::EVENT_EXECUTED,
        ]);
    }

    /** @test */
    public function process_approval_writes_audit_record(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        $execution = AgentSkillExecution::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
        ]);

        $approval = AgentSkillApproval::factory()->create([
            'skill_id' => $this->skill->id,
            'execution_id' => $execution->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        app(ProcessSkillApprovalAction::class)->execute($approval, 'approved', $this->user->id);

        $this->assertDatabaseHas('agent_skill_audits', [
            'skill_id' => $this->skill->id,
            'event_type' => AgentSkillAudit::EVENT_APPROVED,
            'actor_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function skill_audit_trail_is_immutable(): void
    {
        $audit = AgentSkillAudit::create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'actor_id' => $this->user->id,
            'event_type' => AgentSkillAudit::EVENT_EXECUTED,
            'outcome' => AgentSkillAudit::OUTCOME_SUCCESS,
            'occurred_at' => now(),
        ]);

        // Model boot() returns false on updating — Eloquent returns false, no throw.
        $result = $audit->update(['event_type' => 'tampered']);

        $this->assertFalse($result);
        $this->assertEquals(AgentSkillAudit::EVENT_EXECUTED, $audit->fresh()->event_type);
    }

    // 5. Skill Scoring

    /** @test */
    public function record_skill_score_creates_monthly_score_record(): void
    {
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'completed',
            confidence: 85.0,
            durationMs: 250,
        );

        $this->assertDatabaseHas('agent_skill_scores', [
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'period' => now()->format('Y-m'),
            'total_executions' => 1,
            'successful_executions' => 1,
        ]);
    }

    /** @test */
    public function record_skill_score_increments_on_second_execution(): void
    {
        $action = app(RecordSkillScoreAction::class);

        $action->execute($this->skill->id, $this->deployment->id, $this->organization->id, 'completed', 80.0, 200);
        $action->execute($this->skill->id, $this->deployment->id, $this->organization->id, 'failed', null, 500);

        $score = AgentSkillScore::withoutGlobalScopes()
            ->where('skill_id', $this->skill->id)
            ->where('agent_deployment_id', $this->deployment->id)
            ->first();

        $this->assertEquals(2, $score->total_executions);
        $this->assertEquals(1, $score->successful_executions);
        $this->assertEquals(1, $score->failed_executions);
        $this->assertEquals(50.0, $score->success_rate);
    }

    // 6. Skill Failure Recovery

    /** @test */
    public function record_skill_score_tracks_blocked_executions(): void
    {
        app(RecordSkillScoreAction::class)->execute(
            skillId: $this->skill->id,
            deploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            executionStatus: 'skipped',
        );

        $this->assertDatabaseHas('agent_skill_scores', [
            'skill_id' => $this->skill->id,
            'blocked_executions' => 1,
        ]);
    }

    // 7. Skill Delegation

    /** @test */
    public function execute_skill_creates_running_execution_and_fires_event(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        AgentSkillAssignment::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'is_enabled' => true,
        ]);

        $data = new ExecuteSkillData(
            skillId: $this->skill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'manual',
            input: ['query' => 'test'],
        );

        $execution = app(ExecuteSkillAction::class)->execute($data);

        $this->assertInstanceOf(AgentSkillExecution::class, $execution);
        $this->assertEquals('running', $execution->status);
        Event::assertDispatched(SkillExecuted::class);
    }

    /** @test */
    public function execute_skill_is_blocked_for_inactive_skill(): void
    {
        $inactiveSkill = AgentSkill::factory()->create(['is_active' => false]);

        $data = new ExecuteSkillData(
            skillId: $inactiveSkill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'manual',
        );

        $this->expectException(HttpException::class);

        app(ExecuteSkillAction::class)->execute($data);
    }

    // 8. Skill Tenant Isolation

    /** @test */
    public function skill_executions_are_scoped_to_organization(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        AgentSkillAssignment::factory()->create([
            'skill_id' => $this->skill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'is_enabled' => true,
        ]);

        session(['current_organization_id' => $this->organization->id]);

        $data = new ExecuteSkillData(
            skillId: $this->skill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'manual',
        );

        app(ExecuteSkillAction::class)->execute($data);

        // Org 1 owns 1 execution
        $this->assertEquals(1, AgentSkillExecution::withoutGlobalScopes()
            ->where('organization_id', $this->organization->id)->count());

        // Switch to org 2 — scope hides org 1 executions
        $otherOrg = Organization::factory()->create();
        session(['current_organization_id' => $otherOrg->id]);

        $this->assertEquals(0, AgentSkillExecution::count());
    }

    /** @test */
    public function skill_approvals_are_scoped_to_organization(): void
    {
        Event::fake([
            SkillApprovalRequested::class,
            SkillExecuted::class,
            SkillExecutionBlocked::class,
        ]);

        $approvalSkill = AgentSkill::factory()->create([
            'is_active' => true,
            'approval_required' => true,
            'audit_required' => false,
        ]);

        AgentSkillAssignment::factory()->create([
            'skill_id' => $approvalSkill->id,
            'agent_deployment_id' => $this->deployment->id,
            'organization_id' => $this->organization->id,
            'is_enabled' => true,
        ]);

        session(['current_organization_id' => $this->organization->id]);

        $data = new ExecuteSkillData(
            skillId: $approvalSkill->id,
            agentDeploymentId: $this->deployment->id,
            organizationId: $this->organization->id,
            actorId: $this->user->id,
            trigger: 'manual',
        );

        app(ExecuteSkillAction::class)->execute($data);

        $this->assertEquals(1, AgentSkillApproval::withoutGlobalScopes()
            ->where('organization_id', $this->organization->id)->count());

        $otherOrg = Organization::factory()->create();
        session(['current_organization_id' => $otherOrg->id]);

        $this->assertEquals(0, AgentSkillApproval::count());
    }
}

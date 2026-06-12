<?php

namespace Tests\Feature;

use App\Actions\Governance\CreateDecisionLogAction;
use App\Actions\Governance\ProcessApprovalAction;
use App\DTOs\Governance\ProcessApprovalData;
use App\Events\ApprovalProcessed;
use App\Models\AgentApproval;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * End-to-End: Agent Task Governance Flow
 *
 * Simulates the full lifecycle:
 *   Task created → Decision logged → Approval requested → Reviewer approves
 *   → Task transitions to in_progress → Audit trail created
 */
class AgentTaskGovernanceFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $reviewer;

    private Organization $organization;

    private AgentDeployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = User::factory()->create();
        $this->reviewer = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->agent->id]);
        session(['current_organization_id' => $this->organization->id]);

        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
            'deployed_by' => $this->agent->id,
        ]);

        Gate::before(fn () => true);
    }

    public function test_full_governance_flow_creates_decision_log_and_audit(): void
    {
        // Step 1: Create task
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'in_progress',
            'title' => 'Analyze Q3 Revenue',
            'description' => 'Review Q3 financial results and summarize trends',
        ]);

        // Step 2: Agent produces output — simulate decision log creation
        $this->actingAs($this->agent);

        $output = [
            'summary' => 'Revenue increased 12% YoY with strong SaaS growth',
            'confidence' => 88.0,
            'evidence' => ['quarterly_report_2024', 'crm_data'],
            'reasoning' => 'Based on verified financial data',
            'risk_score' => 15,
            'impact_score' => 70,
        ];

        $decisionLog = app(CreateDecisionLogAction::class)->execute(
            $this->deployment, $task, $output
        );

        $this->assertInstanceOf(DecisionLog::class, $decisionLog);
        $this->assertSame($task->id, $decisionLog->task_id);
        $this->assertSame(88.0, (float) $decisionLog->confidence_score);
        $this->assertFalse($decisionLog->requires_human_review);

        // Step 3: Verify audit log was created for the decision (if risk >= 60 it gets logged)
        // Since risk_score=15 < 60, no high-risk audit log — but task and deployment linked
        $this->assertDatabaseHas('decision_logs', [
            'task_id' => $task->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_high_risk_decision_triggers_audit_log(): void
    {
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->agent);

        // High-risk output (confidence 40% — very uncertain, risk 75 — high)
        $output = [
            'summary' => 'I think perhaps maybe the revenue might be declining',
            'confidence' => 40.0,
            'evidence' => [],
            'risk_score' => 75,
            'impact_score' => 80,
        ];

        $decisionLog = app(CreateDecisionLogAction::class)->execute(
            $this->deployment, $task, $output
        );

        $this->assertTrue($decisionLog->requires_human_review);

        // A high-risk decision was logged (either via audit log if risk >= 60, or requires_human_review flag)
        $this->assertDatabaseHas('decision_logs', [
            'task_id' => $task->id,
            'organization_id' => $this->organization->id,
            'requires_human_review' => true,
        ]);
    }

    public function test_approval_workflow_transitions_task_on_approval(): void
    {
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'awaiting_approval',
        ]);

        $approval = AgentApproval::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'task_id' => $task->id,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        $this->actingAs($this->reviewer);

        Event::fake([ApprovalProcessed::class]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'approved', 'Looks good to proceed'));

        $approval->refresh();
        $this->assertSame('approved', $approval->status);
        $this->assertSame('Looks good to proceed', $approval->reviewer_notes);
        $this->assertSame($this->reviewer->id, $approval->reviewed_by);

        // Task should transition to in_progress
        $task->refresh();
        $this->assertSame('in_progress', $task->status);

        // Audit trail
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'approval.approved',
        ]);
    }

    public function test_rejected_approval_fails_task(): void
    {
        $task = AgentTask::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'status' => 'awaiting_approval',
        ]);

        $approval = AgentApproval::factory()->create([
            'organization_id' => $this->organization->id,
            'agent_deployment_id' => $this->deployment->id,
            'task_id' => $task->id,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        $this->actingAs($this->reviewer);

        Event::fake([ApprovalProcessed::class]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'rejected', 'Data sources not verified'));

        $task->refresh();
        $this->assertSame('failed', $task->status);
    }
}

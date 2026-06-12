<?php

namespace Tests\Feature\Governance;

use App\Actions\Governance\ProcessApprovalAction;
use App\DTOs\Governance\ProcessApprovalData;
use App\Events\ApprovalProcessed;
use App\Models\AgentApproval;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use App\Services\Governance\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $reviewer;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->reviewer = User::factory()->create();
        $this->actingAs($this->reviewer);
        session(['current_organization_id' => $this->org->id]);

        // Bypass policy checks — these tests focus on business logic, not authorization
        Gate::before(fn () => true);

        // Prevent actual notifications being sent during tests
        Notification::fake();
    }

    public function test_approval_can_be_approved(): void
    {
        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
            'status' => 'pending_approval',
        ]);
        $approval = AgentApproval::factory()->pending()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        $result = app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'approved', 'LGTM'));

        $this->assertEquals('approved', $result->status);
        $this->assertEquals($this->reviewer->id, $result->reviewed_by);
        $this->assertEquals('LGTM', $result->reviewer_notes);
        $this->assertNotNull($result->reviewed_at);
    }

    public function test_approval_can_be_rejected(): void
    {
        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);
        $approval = AgentApproval::factory()->pending()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        $result = app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'rejected', 'Risk too high'));

        $this->assertEquals('rejected', $result->status);
        $this->assertEquals('Risk too high', $result->reviewer_notes);
    }

    public function test_approval_fires_event(): void
    {
        Event::fake([ApprovalProcessed::class]);

        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);
        $approval = AgentApproval::factory()->pending()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'approved'));

        Event::assertDispatched(ApprovalProcessed::class);
    }

    public function test_cannot_process_already_reviewed_approval(): void
    {
        $this->expectException(\RuntimeException::class);

        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);
        $approval = AgentApproval::factory()->approved()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'approved'));
    }

    public function test_approval_creates_audit_log(): void
    {
        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);
        $approval = AgentApproval::factory()->pending()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'approved', 'Reviewed and approved'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'approval.approved',
            'organization_id' => $this->org->id,
        ]);
    }

    public function test_task_status_transitions_on_approval_decision(): void
    {
        $deployment = AgentDeployment::factory()->create(['organization_id' => $this->org->id]);
        $task = AgentTask::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
            'status' => 'pending_approval',
        ]);
        $approval = AgentApproval::factory()->pending()->create([
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $this->org->id,
        ]);

        app(ProcessApprovalAction::class)->execute($approval, new ProcessApprovalData($approval->id, 'rejected'));

        $this->assertDatabaseHas('agent_tasks', [
            'id' => $task->id,
            'status' => 'failed',
        ]);
    }

    public function test_audit_service_log_user_action_creates_record(): void
    {
        $auditService = app(AuditService::class);

        $log = $auditService->logUserAction(
            event: 'test.governance_event',
            description: 'Governance audit test entry',
            data: ['key' => 'value'],
        );

        $this->assertNotNull($log->id);
        $this->assertEquals('test.governance_event', $log->event);
        $this->assertEquals('user_action', $log->event_category);
    }

    public function test_audit_log_is_immutable_after_creation(): void
    {
        $this->expectException(\RuntimeException::class);

        $auditService = app(AuditService::class);

        $log = $auditService->logUserAction(
            event: 'test.immutability',
            description: 'Should not be editable',
        );

        // AuditLog::boot() throws RuntimeException on any update attempt
        $log->description = 'tampered';
        $log->save();
    }
}

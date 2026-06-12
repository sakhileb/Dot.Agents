<?php

namespace Tests\Feature\Actions\Workflows;

use App\Actions\Workflows\CreateWorkflowAction;
use App\Actions\Workflows\DeleteWorkflowAction;
use App\Actions\Workflows\UpdateWorkflowStatusAction;
use App\DTOs\Workflows\CreateWorkflowData;
use App\Events\WorkflowCreated;
use App\Events\WorkflowDeleted;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WorkflowLifecycleActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Gate::before(fn () => true);
    }

    // ── CreateWorkflowAction ─────────────────────────────────────────────────

    public function test_create_workflow_persists_to_database(): void
    {
        $this->actingAs($this->user);
        Event::fake([WorkflowCreated::class]);

        $data = new CreateWorkflowData('My Workflow', 'manual', 'A description');

        $workflow = app(CreateWorkflowAction::class)->execute($this->organization, $data);

        $this->assertInstanceOf(AgentWorkflow::class, $workflow);
        $this->assertDatabaseHas('agent_workflows', [
            'name' => 'My Workflow',
            'organization_id' => $this->organization->id,
            'status' => 'draft',
            'trigger_type' => 'manual',
        ]);
    }

    public function test_create_workflow_fires_workflow_created_event(): void
    {
        $this->actingAs($this->user);
        Event::fake([WorkflowCreated::class]);

        $data = new CreateWorkflowData('Event Workflow', 'scheduled');

        $workflow = app(CreateWorkflowAction::class)->execute($this->organization, $data);

        Event::assertDispatched(WorkflowCreated::class, function ($e) use ($workflow) {
            return $e->workflow->id === $workflow->id;
        });
    }

    public function test_create_workflow_sets_created_by_to_authenticated_user(): void
    {
        $this->actingAs($this->user);
        Event::fake([WorkflowCreated::class]);

        $data = new CreateWorkflowData('Auth Workflow', 'manual');

        $workflow = app(CreateWorkflowAction::class)->execute($this->organization, $data);

        $this->assertSame($this->user->id, $workflow->created_by);
    }

    public function test_delete_workflow_soft_deletes_record(): void
    {
        $this->actingAs($this->user);
        Event::fake([WorkflowDeleted::class]);

        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        app(DeleteWorkflowAction::class)->execute($workflow);

        $this->assertSoftDeleted('agent_workflows', ['id' => $workflow->id]);
    }

    public function test_delete_workflow_fires_workflow_deleted_event(): void
    {
        $this->actingAs($this->user);
        Event::fake([WorkflowDeleted::class]);

        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $workflowId = $workflow->id;
        $orgId = $workflow->organization_id;

        app(DeleteWorkflowAction::class)->execute($workflow);

        Event::assertDispatched(WorkflowDeleted::class, function ($e) use ($workflowId, $orgId) {
            return $e->workflowId === $workflowId && $e->organizationId === $orgId;
        });
    }

    // ── UpdateWorkflowStatusAction ───────────────────────────────────────────

    public function test_publish_sets_status_to_active(): void
    {
        $this->actingAs($this->user);

        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft',
        ]);

        $result = app(UpdateWorkflowStatusAction::class)->publish($workflow);

        $this->assertSame('active', $result->status);
        $this->assertDatabaseHas('agent_workflows', ['id' => $workflow->id, 'status' => 'active']);
    }

    public function test_unpublish_reverts_status_to_draft(): void
    {
        $this->actingAs($this->user);

        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $result = app(UpdateWorkflowStatusAction::class)->unpublish($workflow);

        $this->assertSame('draft', $result->status);
        $this->assertDatabaseHas('agent_workflows', ['id' => $workflow->id, 'status' => 'draft']);
    }
}

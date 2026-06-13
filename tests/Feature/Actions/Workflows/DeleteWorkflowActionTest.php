<?php

namespace Tests\Feature\Actions\Workflows;

use App\Actions\Workflows\DeleteWorkflowAction;
use App\Events\WorkflowDeleted;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteWorkflowActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_deletes_workflow(): void
    {
        Event::fake([WorkflowDeleted::class]);

        $workflow = AgentWorkflow::factory()->create(['organization_id' => $this->organization->id]);

        app(DeleteWorkflowAction::class)->execute($workflow);

        $this->assertSoftDeleted('agent_workflows', ['id' => $workflow->id]);
        Event::assertDispatched(WorkflowDeleted::class);
    }

    #[Test]
    public function test_fires_event_with_workflow_info(): void
    {
        Event::fake([WorkflowDeleted::class]);

        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'My Workflow',
        ]);
        $id = $workflow->id;

        app(DeleteWorkflowAction::class)->execute($workflow);

        Event::assertDispatched(WorkflowDeleted::class, function (WorkflowDeleted $e) use ($id) {
            return $e->workflowId === $id;
        });
    }
}

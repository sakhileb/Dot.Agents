<?php

namespace Tests\Feature\Actions\Workflows;

use App\Actions\Workflows\CreateWorkflowAction;
use App\DTOs\Workflows\CreateWorkflowData;
use App\Events\WorkflowCreated;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateWorkflowActionTest extends TestCase
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
    public function test_creates_workflow_as_draft(): void
    {
        Event::fake([WorkflowCreated::class]);

        $data = new CreateWorkflowData(
            name: 'Onboarding Flow',
            triggerType: 'manual',
            description: 'Handles new employee onboarding',
        );

        $workflow = app(CreateWorkflowAction::class)->execute($this->organization, $data);

        $this->assertInstanceOf(AgentWorkflow::class, $workflow);
        $this->assertEquals('draft', $workflow->status);
        $this->assertEquals('Onboarding Flow', $workflow->name);
        $this->assertEquals($this->organization->id, $workflow->organization_id);
        $this->assertDatabaseHas('agent_workflows', ['id' => $workflow->id, 'status' => 'draft']);
        Event::assertDispatched(WorkflowCreated::class);
    }

    #[Test]
    public function test_assigns_uuid_to_workflow(): void
    {
        Event::fake();

        $data = new CreateWorkflowData(name: 'My Workflow', triggerType: 'scheduled');

        $workflow = app(CreateWorkflowAction::class)->execute($this->organization, $data);

        $this->assertNotNull($workflow->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}$/', $workflow->uuid);
    }
}

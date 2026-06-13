<?php

namespace Tests\Feature\Actions\Workflows;

use App\Actions\Workflows\UpdateWorkflowStatusAction;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateWorkflowStatusActionTest extends TestCase
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
    public function test_publishes_workflow(): void
    {
        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'draft',
        ]);

        $result = app(UpdateWorkflowStatusAction::class)->publish($workflow);

        $this->assertEquals('active', $result->status);
        $this->assertDatabaseHas('agent_workflows', ['id' => $workflow->id, 'status' => 'active']);
    }

    #[Test]
    public function test_unpublishes_workflow(): void
    {
        $workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        $result = app(UpdateWorkflowStatusAction::class)->unpublish($workflow);

        $this->assertEquals('draft', $result->status);
        $this->assertDatabaseHas('agent_workflows', ['id' => $workflow->id, 'status' => 'draft']);
    }
}

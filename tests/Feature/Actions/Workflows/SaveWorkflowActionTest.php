<?php

namespace Tests\Feature\Actions\Workflows;

use App\Actions\Workflows\SaveWorkflowAction;
use App\DTOs\Workflows\SaveWorkflowData;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SaveWorkflowActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private AgentWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        $this->workflow = AgentWorkflow::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        Gate::before(fn () => true);
    }

    private function dto(array $nodes = [], array $connections = []): SaveWorkflowData
    {
        return new SaveWorkflowData($this->workflow->id, $this->user->id, $nodes, $connections);
    }

    public function test_saves_nodes_to_workflow(): void
    {
        $this->actingAs($this->user);

        $nodes = [
            ['id' => 'node-1', 'agent_key' => 'revenue-analytics', 'label' => 'Revenue Agent', 'x' => 100, 'y' => 200, 'config' => []],
            ['id' => 'node-2', 'agent_key' => 'compliance-check', 'label' => 'Compliance Agent', 'x' => 300, 'y' => 200, 'config' => []],
        ];
        $connections = [['id' => 'conn-1', 'from' => 'node-1', 'to' => 'node-2']];

        $result = app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto($nodes, $connections));

        $this->assertInstanceOf(AgentWorkflow::class, $result);
        $this->assertCount(2, $result->nodes);
        $this->assertCount(1, $result->connections);
    }

    public function test_replaces_existing_nodes_on_save(): void
    {
        $this->actingAs($this->user);

        app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto([
            ['id' => 'old-node', 'agent_key' => 'old-agent', 'config' => []],
        ]));

        app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto([
            ['id' => 'new-node-1', 'agent_key' => 'new-agent-1', 'config' => []],
            ['id' => 'new-node-2', 'agent_key' => 'new-agent-2', 'config' => []],
        ]));

        $this->assertCount(2, $this->workflow->fresh()->nodes);
    }

    public function test_throws_when_node_missing_required_fields(): void
    {
        $this->actingAs($this->user);
        $this->expectException(\InvalidArgumentException::class);

        app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto([
            ['label' => 'Missing id and agent_key'],
        ]));
    }

    public function test_throws_when_connection_missing_required_fields(): void
    {
        $this->actingAs($this->user);
        $this->expectException(\InvalidArgumentException::class);

        app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto(
            [['id' => 'n1', 'agent_key' => 'agent', 'config' => []]],
            [['id' => 'missing-from-to']],
        ));
    }

    public function test_creates_audit_log_on_save(): void
    {
        $this->actingAs($this->user);

        app(SaveWorkflowAction::class)->execute($this->workflow, $this->dto());

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'event' => 'workflow.saved',
        ]);
    }
}

<?php

namespace App\Livewire\Workflows;

use App\Actions\Workflows\SaveWorkflowAction;
use App\Models\Agent;
use App\Models\AgentWorkflow;
use App\Services\AI\GraphWorkflowEngineService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * WorkflowBuilder — visual graph canvas component.
 *
 * Responsibilities:
 *  – Persist nodes and connections on save
 *  – Provide the Alpine.js canvas with the current graph state as JSON
 *  – Trigger graph execution
 *  – Remain thin: no business logic beyond CRUD of canvas state
 */
class WorkflowBuilder extends Component
{
    public AgentWorkflow $workflow;

    /** Raw node array — synced from Alpine via save() */
    public array $nodes = [];

    /** Raw connection array — synced from Alpine via save() */
    public array $connections = [];

    /** Validated flash state */
    public ?string $flashMessage = null;

    public string $flashType = 'success';

    // ──────────────────────────────────────────────
    // Lifecycle
    // ──────────────────────────────────────────────

    public function mount(int $workflowId): void
    {
        $this->workflow = AgentWorkflow::where('organization_id', session('current_organization_id'))
            ->findOrFail($workflowId);

        $this->loadGraph();
    }

    private function loadGraph(): void
    {
        $this->nodes = $this->workflow->nodes()
            ->get(['uuid', 'agent_key', 'label', 'position_x', 'position_y', 'config'])
            ->map(fn ($n) => [
                'id' => $n->uuid,
                'agent_key' => $n->agent_key,
                'label' => $n->label ?? $n->agent_key,
                'x' => $n->position_x,
                'y' => $n->position_y,
                'config' => $n->config ?? [],
            ])
            ->toArray();

        $this->connections = $this->workflow->connections()
            ->get(['uuid', 'from_node_uuid', 'to_node_uuid', 'condition', 'label'])
            ->map(fn ($c) => [
                'id' => $c->uuid,
                'from' => $c->from_node_uuid,
                'to' => $c->to_node_uuid,
                'condition' => $c->condition,
                'label' => $c->label,
            ])
            ->toArray();
    }

    // ──────────────────────────────────────────────
    // Computed
    // ──────────────────────────────────────────────

    #[Computed]
    public function availableAgents()
    {
        // Cache agent catalog for 5 minutes — used in canvas palette, rarely changes
        return Cache::remember('workflow_available_agents', 300, fn () => Agent::active()
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'category_id'])
            ->toArray()
        );
    }

    // ──────────────────────────────────────────────
    // Canvas actions (called from Alpine via wire:call)
    // ──────────────────────────────────────────────

    /**
     * Add a new node at the given canvas position.
     */
    public function addNode(string $agentKey, int $x = 200, int $y = 200): void
    {
        $this->nodes[] = [
            'id' => (string) Str::uuid(),
            'agent_key' => $agentKey,
            'label' => $agentKey,
            'x' => $x,
            'y' => $y,
            'config' => [],
        ];

        $this->syncCanvas();
    }

    /**
     * Connect two nodes with an optional condition.
     */
    public function connectNodes(string $fromId, string $toId, ?array $condition = null): void
    {
        // Prevent self-loops
        if ($fromId === $toId) {
            return;
        }

        // Prevent duplicate connections
        foreach ($this->connections as $existing) {
            if ($existing['from'] === $fromId && $existing['to'] === $toId) {
                return;
            }
        }

        $this->connections[] = [
            'id' => (string) Str::uuid(),
            'from' => $fromId,
            'to' => $toId,
            'condition' => $condition,
            'label' => null,
        ];

        $this->syncCanvas();
    }

    /**
     * Update a node's canvas position (called after drag-end).
     * Alpine already has the correct position from live drag — no sync needed.
     */
    public function moveNode(string $nodeId, int $x, int $y): void
    {
        foreach ($this->nodes as &$node) {
            if ($node['id'] === $nodeId) {
                $node['x'] = $x;
                $node['y'] = $y;
                break;
            }
        }
    }

    /**
     * Remove a node and all its associated connections.
     */
    public function removeNode(string $nodeId): void
    {
        $this->nodes = array_values(
            array_filter($this->nodes, fn ($n) => $n['id'] !== $nodeId)
        );

        $this->connections = array_values(
            array_filter($this->connections, fn ($c) => $c['from'] !== $nodeId && $c['to'] !== $nodeId)
        );

        $this->syncCanvas();
    }

    /**
     * Remove a single connection edge.
     */
    public function removeConnection(string $connectionId): void
    {
        $this->connections = array_values(
            array_filter($this->connections, fn ($c) => $c['id'] !== $connectionId)
        );

        $this->syncCanvas();
    }

    /**
     * Dispatch a browser event carrying the current canvas state so Alpine
     * can update its local nodes/connections arrays synchronously.
     * Called after every canvas-mutating server method.
     */
    private function syncCanvas(): void
    {
        $this->dispatch('canvas-synced', nodes: $this->nodes, connections: $this->connections);
    }

    // ──────────────────────────────────────────────
    // Persistence
    // ──────────────────────────────────────────────

    /**
     * Persist the current canvas state to the database.
     */
    public function save(): void
    {
        app(SaveWorkflowAction::class)->execute($this->workflow, $this->nodes, $this->connections);

        $this->flashMessage = 'Workflow saved successfully.';
        $this->flashType = 'success';

        $this->dispatch('graph-saved');
    }

    // ──────────────────────────────────────────────
    // Execution
    // ──────────────────────────────────────────────

    /**
     * Trigger graph execution and return the execution ID.
     */
    public function run(): void
    {
        if (empty($this->nodes)) {
            $this->flashMessage = 'Add at least one agent node to the canvas before running.';
            $this->flashType = 'warning';
            return;
        }

        $this->save();

        try {
            $execution = app(GraphWorkflowEngineService::class)->execute(
                workflow: $this->workflow,
                triggeredBy: Auth::id(),
            );

            $this->flashMessage = "Execution #{$execution->id} started — status: {$execution->status}";
            $this->flashType = $execution->status === 'completed' ? 'success' : 'warning';

            $this->dispatch('execution-started', executionId: $execution->id);
        } catch (\Throwable $e) {
            $this->flashMessage = 'Workflow execution failed: ' . $e->getMessage();
            $this->flashType = 'error';
        }
    }

    // ──────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────

    public function render()
    {
        return view('livewire.workflows.workflow-builder');
    }
}

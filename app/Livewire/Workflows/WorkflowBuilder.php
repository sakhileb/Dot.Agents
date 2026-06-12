<?php

namespace App\Livewire\Workflows;

use App\Actions\Workflows\SaveWorkflowAction;
use App\Actions\Workflows\UpdateWorkflowStatusAction;
use App\DTOs\Workflows\SaveWorkflowData;
use App\Livewire\Concerns\ManagesWorkflowCanvas;
use App\Models\Agent;
use App\Models\AgentWorkflow;
use App\Services\AI\GraphWorkflowEngineService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
    use ManagesWorkflowCanvas;

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
    // Persistence
    // ──────────────────────────────────────────────

    /**
     * Persist the current canvas state to the database.
     */
    public function save(): void
    {
        app(SaveWorkflowAction::class)->execute(
            $this->workflow,
            new SaveWorkflowData($this->workflow->id, Auth::id(), $this->nodes, $this->connections)
        );

        $this->flashMessage = 'Workflow saved as draft.';
        $this->flashType = 'success';

        $this->dispatch('graph-saved');
    }

    /**
     * Publish the workflow — saves the graph then sets status to 'active'.
     */
    public function publish(): void
    {
        if (empty($this->nodes)) {
            $this->flashMessage = 'Add at least one agent node before publishing.';
            $this->flashType = 'warning';

            return;
        }

        app(SaveWorkflowAction::class)->execute(
            $this->workflow,
            new SaveWorkflowData($this->workflow->id, Auth::id(), $this->nodes, $this->connections)
        );

        app(UpdateWorkflowStatusAction::class)->publish($this->workflow);
        $this->workflow->refresh();

        $this->flashMessage = 'Workflow published and is now active.';
        $this->flashType = 'success';
    }

    /**
     * Unpublish — revert status back to draft.
     */
    public function unpublish(): void
    {
        app(UpdateWorkflowStatusAction::class)->unpublish($this->workflow);
        $this->workflow->refresh();

        $this->flashMessage = 'Workflow reverted to draft.';
        $this->flashType = 'success';
    }

    // ──────────────────────────────────────────────
    // Compatibility shim
    // ──────────────────────────────────────────────

    /**
     * Older compiled blade views (with @entangle) call toJSON() on this
     * component when Alpine initialises. This method exists solely to
     * prevent a MethodNotFoundException 500 if a stale compiled view is
     * still cached on the production server.  It is never called by the
     * current blade (which uses Js::from() instead of @entangle).
     */
    public function toJSON(): array
    {
        return [];
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
            $this->flashMessage = 'Workflow execution failed: '.$e->getMessage();
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

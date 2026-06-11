<?php

namespace App\Livewire\Workflows;

use App\Actions\Workflows\CreateWorkflowAction;
use App\Actions\Workflows\DeleteWorkflowAction;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class WorkflowList extends Component
{
    public bool $showCreateModal = false;

    #[Validate('required|string|min:2|max:100')]
    public string $newName = '';

    #[Validate('nullable|string|max:300')]
    public string $newDescription = '';

    #[Validate('required|in:manual,scheduled,event,webhook')]
    public string $newTrigger = 'manual';

    #[Computed]
    public function workflows()
    {
        $orgId = session('current_organization_id');

        return AgentWorkflow::withCount(['nodes'])
            ->where('organization_id', $orgId)
            ->orderByDesc('updated_at')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset('newName', 'newDescription', 'newTrigger');
        $this->newTrigger = 'manual';
        $this->showCreateModal = true;
    }

    public function createWorkflow(): void
    {
        $this->validate();

        $organization = Organization::findOrFail(session('current_organization_id'));

        $workflow = app(CreateWorkflowAction::class)->execute($organization, [
            'name' => $this->newName,
            'description' => $this->newDescription ?: null,
            'trigger_type' => $this->newTrigger,
        ]);

        $this->showCreateModal = false;
        $this->redirect(route('workflows.builder', $workflow), navigate: true);
    }

    public function deleteWorkflow(int $id): void
    {
        $workflow = AgentWorkflow::where('organization_id', session('current_organization_id'))
            ->findOrFail($id);

        app(DeleteWorkflowAction::class)->execute($workflow);

        unset($this->workflows);
        session()->flash('status', "Workflow \"{$workflow->name}\" deleted.");
    }

    public function render()
    {
        return view('livewire.workflows.workflow-list', [
            'workflows' => $this->workflows,
        ]);
    }
}

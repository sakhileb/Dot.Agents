<?php

namespace App\Livewire\Marketplace;

use App\Actions\Agents\DeployAgentAction;
use App\DTOs\Agents\DeployAgentData;
use App\Livewire\Forms\DeployAgentForm;
use App\Models\Agent;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
class AgentMarketplace extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedDepartment = '';

    public string $selectedCategory = '';

    public string $selectedType = '';

    public string $sortBy = 'featured';

    public ?Agent $previewAgent = null;

    public bool $showDeployModal = false;

    public ?int $deployingAgentId = null;

    public DeployAgentForm $deployForm;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function agents()
    {
        return Agent::active()
            ->when($this->search, fn ($q) => $q->where(function ($sq) {
                $sq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('tagline', 'like', "%{$this->search}%");
            }))
            ->when($this->selectedDepartment, fn ($q) => $q->whereHas('agentDepartment', fn ($dq) => $dq->where('slug', $this->selectedDepartment))
            )
            ->when($this->selectedCategory, fn ($q) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $this->selectedCategory))
            )
            ->when($this->selectedType, fn ($q) => $q->where('agent_type', $this->selectedType))
            ->when($this->sortBy === 'featured', fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('avg_rating'))
            ->when($this->sortBy === 'rating', fn ($q) => $q->orderByDesc('avg_rating'))
            ->when($this->sortBy === 'popular', fn ($q) => $q->orderByDesc('total_deployments'))
            ->when($this->sortBy === 'price_asc', fn ($q) => $q->orderBy('base_price'))
            ->when($this->sortBy === 'price_desc', fn ($q) => $q->orderByDesc('base_price'))
            ->with(['agentDepartment', 'category'])
            ->paginate(12);
    }

    #[Computed]
    public function departments()
    {
        return AgentDepartment::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function categories()
    {
        return AgentCategory::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function previewAgent(int $agentId): void
    {
        $this->previewAgent = Agent::with(['agentDepartment', 'category', 'reviews' => fn ($q) => $q->take(3)])
            ->find($agentId);
    }

    public function closePreview(): void
    {
        $this->previewAgent = null;
    }

    public function startDeploy(int $agentId): void
    {
        $agent = Agent::find($agentId);
        $this->deployingAgentId = $agentId;
        $this->deployForm->deploymentName = $agent->name;
        $this->deployForm->deploymentMode = $agent->default_deployment_mode ?? 'advisory';
        $this->showDeployModal = true;
        $this->previewAgent = null;
    }

    public function deploy(): void
    {
        $this->deployForm->validate();

        $formData = $this->deployForm->toArray();

        $data = DeployAgentData::fromArray([
            'agent_id' => $this->deployingAgentId,
            'organization_id' => session('current_organization_id'),
            'deployed_by' => auth()->id(),
            'name' => $formData['deployment_name'],
            'deployment_mode' => $formData['deployment_mode'],
            'department_id' => $formData['department_id'],
            'custom_instructions' => $formData['custom_instructions'],
        ]);

        $deployment = app(DeployAgentAction::class)->execute($data);

        $this->showDeployModal = false;
        $this->deployingAgentId = null;
        $this->deployForm->reset();

        $this->dispatch('agent-deployed', deploymentId: $deployment->id);
        session()->flash('success', "Agent '{$deployment->name}' deployed successfully!");
    }

    public function render()
    {
        return view('livewire.marketplace.agent-marketplace');
    }
}

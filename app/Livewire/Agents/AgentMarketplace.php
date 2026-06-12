<?php

declare(strict_types=1);

namespace App\Livewire\Agents;

use App\Models\Agent;
use App\Models\AgentCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AgentMarketplace extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $categorySlug = '';

    #[Url(as: 'type')]
    public string $agentType = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'featured';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategorySlug(): void
    {
        $this->resetPage();
    }

    public function updatedAgentType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return AgentCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function agents()
    {
        return Agent::where('status', 'published')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('tagline', 'like', "%{$this->search}%")
                    ->orWhere('specialization', 'like', "%{$this->search}%");
            }))
            ->when($this->categorySlug, fn ($q) => $q->whereHas(
                'category',
                fn ($q) => $q->where('slug', $this->categorySlug)
            ))
            ->when($this->agentType, fn ($q) => $q->where('agent_type', $this->agentType))
            ->when($this->sortBy === 'featured', fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('avg_rating'))
            ->when($this->sortBy === 'rating', fn ($q) => $q->orderByDesc('avg_rating'))
            ->when($this->sortBy === 'deployments', fn ($q) => $q->orderByDesc('total_deployments'))
            ->when($this->sortBy === 'newest', fn ($q) => $q->orderByDesc('created_at'))
            ->with('category')
            ->paginate(12);
    }

    #[Computed]
    public function agentTypes(): array
    {
        return Agent::where('status', 'published')
            ->distinct()
            ->pluck('agent_type')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.agents.agent-marketplace');
    }
}

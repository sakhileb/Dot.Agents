<?php

namespace App\Livewire\Agents;

use App\Models\AgentDeployment;
use App\Services\Governance\ScorecardService;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ScorecardViewer extends Component
{
    public int $deploymentId;

    public string $period = '30d';

    public function mount(int $deploymentId): void
    {
        $this->deploymentId = $deploymentId;
    }

    public function getDeploymentProperty(): AgentDeployment
    {
        return AgentDeployment::with(['agent.agentDepartment', 'scorecards' => fn ($q) => $q->latest()->limit(6)])
            ->findOrFail($this->deploymentId);
    }

    public function getScorecardProperty()
    {
        return $this->deployment->latestScorecard;
    }

    public function getHistoryProperty()
    {
        return $this->deployment->scorecards()->latest()->limit(6)->get();
    }

    public function recalculate(): void
    {
        app(ScorecardService::class)->calculatePeriodScorecard(
            $this->deployment,
            now()->subDays((int) $this->period)->startOfDay(),
            now()->endOfDay()
        );
        session()->flash('status', 'Scorecard recalculated.');
    }

    public function render()
    {
        return view('livewire.agents.scorecard-viewer');
    }
}

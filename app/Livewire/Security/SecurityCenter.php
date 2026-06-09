<?php

namespace App\Livewire\Security;

use App\Actions\Security\ResolveSecurityEventAction;
use App\Models\AgentDeployment;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Services\Governance\DigitalImmuneSystem;
use Livewire\Component;
use Livewire\WithPagination;

class SecurityCenter extends Component
{
    use WithPagination;

    public string $filterSeverity = '';

    public string $filterType = '';

    public ?array $disReport = null;

    public bool $runningDIS = false;

    public function getOrganizationIdProperty(): ?int
    {
        return session('current_organization_id');
    }

    public function getEventsProperty()
    {
        return SecurityEvent::where('organization_id', $this->organizationId)
            ->when($this->filterSeverity, fn ($q) => $q->where('severity', $this->filterSeverity))
            ->when($this->filterType, fn ($q) => $q->where('event_type', $this->filterType))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $events = SecurityEvent::where('organization_id', $this->organizationId);

        return [
            'total_24h' => (clone $events)->where('created_at', '>=', now()->subDay())->count(),
            'critical' => (clone $events)->where('severity', 'critical')->where('status', 'open')->count(),
            'auto_remediated' => (clone $events)->where('auto_remediated', true)->count(),
            'quarantined' => AgentDeployment::where('organization_id', $this->organizationId)->where('status', 'suspended')->count(),
        ];
    }

    public function runDISCheck(): void
    {
        $this->runningDIS = true;
        $org = Organization::find($this->organizationId);
        if ($org) {
            $this->disReport = app(DigitalImmuneSystem::class)->runHealthCheck($org);
        }
        $this->runningDIS = false;
    }

    public function resolveEvent(int $id): void
    {
        app(ResolveSecurityEventAction::class)->execute($id);
    }

    public function render()
    {
        return view('livewire.security.security-center');
    }
}

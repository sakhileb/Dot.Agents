<?php

namespace App\Livewire\Concerns;

use App\Actions\Agents\DeployAgentAction;
use App\DTOs\Agents\DeployAgentData;
use App\Livewire\Forms\DeployAgentForm;
use App\Models\Agent;
use Illuminate\Support\Facades\Auth;

/**
 * ManagesAgentDeploy
 *
 * Encapsulates the deploy modal state and deploy workflow for the marketplace.
 * Include in AgentMarketplace via `use ManagesAgentDeploy;`.
 *
 * The trait expects a `DeployAgentForm $deployForm` property on the component.
 */
trait ManagesAgentDeploy
{
    public bool $showDeployModal = false;

    public ?int $deployingAgentId = null;

    public function startDeploy(int $agentId): void
    {
        $agent = Agent::find($agentId);
        if (! $agent) {
            return;
        }

        $this->deployingAgentId = $agentId;
        $this->deployForm->deployment_name = $agent->name;
        $this->deployForm->deployment_mode = $agent->default_deployment_mode ?? 'advisory';
        $this->showDeployModal = true;
        $this->previewAgent = null;
    }

    public function closeDeploy(): void
    {
        $this->showDeployModal = false;
        $this->deployingAgentId = null;
        $this->deployForm->reset();
    }

    public function deploy(): void
    {
        $this->deployForm->validate();

        $orgId = session('current_organization_id');
        abort_if(! $orgId, 403, 'No active organization context.');

        $formData = $this->deployForm->toArray();

        $data = DeployAgentData::fromArray([
            'agent_id' => $this->deployingAgentId,
            'organization_id' => $orgId,
            'deployed_by' => Auth::id(),
            'name' => $formData['deployment_name'],
            'deployment_mode' => $formData['deployment_mode'],
            'department_id' => $formData['department_id'],
            'custom_instructions' => $formData['custom_instructions'],
            'confidence_threshold' => $formData['confidence_threshold'] ?? 75.0,
        ]);

        $deployment = app(DeployAgentAction::class)->execute($data);

        $this->closeDeploy();
        $this->dispatch('agent-deployed', deploymentId: $deployment->id);
        session()->flash('success', "Agent '{$deployment->name}' deployed successfully!");
    }
}

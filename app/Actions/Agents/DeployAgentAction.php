<?php

namespace App\Actions\Agents;

use App\DTOs\Agents\DeployAgentData;
use App\Events\AgentDeployed;
use App\Models\AgentDeployment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DeployAgentAction
{
    public function execute(DeployAgentData $data): AgentDeployment
    {
        Gate::authorize('create', [AgentDeployment::class, $data->organizationId]);

        $deployment = AgentDeployment::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $data->organizationId,
            'agent_id' => $data->agentId,
            'department_id' => $data->departmentId,
            'deployed_by' => $data->deployedBy,
            'name' => $data->name,
            'deployment_mode' => $data->deploymentMode,
            'confidence_threshold' => $data->confidenceThreshold,
            'custom_instructions' => $data->customInstructions,
            'requires_human_approval' => $data->deploymentMode !== 'autonomous',
            'status' => 'active',
        ]);

        $deployment->agent()->increment('total_deployments');

        event(new AgentDeployed($deployment));

        return $deployment;
    }
}

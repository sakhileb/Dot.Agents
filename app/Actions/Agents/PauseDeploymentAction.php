<?php

namespace App\Actions\Agents;

use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class PauseDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(AgentDeployment $deployment): AgentDeployment
    {
        Gate::authorize('update', $deployment);

        $deployment->update(['status' => 'paused']);

        $this->auditService->logUserAction(
            event: 'deployment.paused',
            description: "Deployment {$deployment->display_name} paused",
            subject: $deployment,
        );

        return $deployment->refresh();
    }
}

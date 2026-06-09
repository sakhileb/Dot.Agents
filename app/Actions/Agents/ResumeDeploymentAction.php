<?php

namespace App\Actions\Agents;

use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class ResumeDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(AgentDeployment $deployment): AgentDeployment
    {
        Gate::authorize('update', $deployment);

        $deployment->update(['status' => 'active']);

        $this->auditService->logUserAction(
            event: 'deployment.resumed',
            description: "Deployment {$deployment->display_name} resumed",
            subject: $deployment,
        );

        return $deployment->refresh();
    }
}

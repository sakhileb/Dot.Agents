<?php

namespace App\Actions\Agents;

use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class DecommissionDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(AgentDeployment $deployment, string $reason = ''): AgentDeployment
    {
        Gate::authorize('delete', $deployment);

        $deployment->update([
            'status' => 'decommissioned',
            'decommissioned_at' => now(),
        ]);

        $this->auditService->logUserAction(
            event: 'deployment.decommissioned',
            description: "Deployment {$deployment->display_name} decommissioned. Reason: {$reason}",
            subject: $deployment,
            metadata: ['reason' => $reason],
        );

        return $deployment->refresh();
    }
}

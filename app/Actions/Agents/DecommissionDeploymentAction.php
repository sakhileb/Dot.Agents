<?php

namespace App\Actions\Agents;

use App\Events\AgentDecommissioned;
use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class DecommissionDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Permanently decommission an AgentDeployment.
     *
     * Marks the deployment as 'decommissioned', records the decommissioned_at
     * timestamp, writes a full audit trail entry, and fires AgentDecommissioned
     * so downstream jobs can clean up memory, tasks, and integrations.
     *
     * @param  AgentDeployment  $deployment  The deployment to decommission.
     * @param  string  $reason  Human-readable decommission reason for audit trail.
     * @return AgentDeployment The refreshed, decommissioned deployment.
     *
     * @throws AuthorizationException When actor lacks 'delete' permission.
     */
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

        event(new AgentDecommissioned($deployment, $reason));

        return $deployment->refresh();
    }
}

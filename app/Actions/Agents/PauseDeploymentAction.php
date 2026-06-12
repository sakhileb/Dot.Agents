<?php

namespace App\Actions\Agents;

use App\Events\AgentPaused;
use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class PauseDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Pause an active AgentDeployment.
     *
     * Sets deployment status to 'paused', writes an audit trail entry,
     * and fires the AgentPaused domain event.
     *
     * @param  AgentDeployment  $deployment  The deployment to pause.
     * @return AgentDeployment The refreshed deployment with updated status.
     *
     * @throws AuthorizationException When actor lacks 'update' permission.
     */
    public function execute(AgentDeployment $deployment): AgentDeployment
    {
        Gate::authorize('update', $deployment);

        $deployment->update(['status' => 'paused']);

        $this->auditService->logUserAction(
            event: 'deployment.paused',
            description: "Deployment {$deployment->display_name} paused",
            subject: $deployment,
        );

        event(new AgentPaused($deployment));

        return $deployment->refresh();
    }
}

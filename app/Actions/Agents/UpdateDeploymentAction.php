<?php

namespace App\Actions\Agents;

use App\DTOs\Agents\UpdateDeploymentData;
use App\Events\AgentUpdated;
use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class UpdateDeploymentAction
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Update allowed configuration fields on an AgentDeployment.
     *
     * Only attributes listed in the allowlist are written; all others are ignored.
     * Fires AgentUpdated with old/new snapshots so audit and notification listeners
     * can react to configuration drift.
     *
     * @param  AgentDeployment  $deployment  The deployment to update.
     * @param  UpdateDeploymentData  $data  Typed DTO carrying updated field values.
     * @return AgentDeployment The refreshed deployment after update.
     *
     * @throws AuthorizationException When actor lacks 'update' permission.
     */
    public function execute(AgentDeployment $deployment, UpdateDeploymentData $data): AgentDeployment
    {
        Gate::authorize('update', $deployment);

        $allowed = [
            'name', 'alias', 'custom_instructions', 'deployment_mode',
            'confidence_threshold', 'requires_human_approval',
            'enable_memory', 'enable_long_term_memory', 'memory_retention_days',
            'risk_tolerance', 'allowed_actions', 'restricted_actions',
            'model_override', 'model_config_override', 'context_config',
            'notification_config', 'integration_config', 'metadata',
        ];

        $old = $deployment->only($allowed);
        $updates = array_intersect_key($data->toArray(), array_flip($allowed));

        $deployment->update($updates);

        $this->auditService->logUserAction(
            event: 'deployment.updated',
            description: "Deployment '{$deployment->display_name}' settings updated",
            subject: $deployment,
            metadata: ['old' => $old, 'new' => $updates]
        );

        event(new AgentUpdated($deployment, ['old' => $old, 'new' => $updates]));

        return $deployment->refresh();
    }
}

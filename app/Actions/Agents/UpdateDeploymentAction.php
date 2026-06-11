<?php

namespace App\Actions\Agents;

use App\Events\AgentUpdated;
use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class UpdateDeploymentAction
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function execute(AgentDeployment $deployment, array $data): AgentDeployment
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
        $updates = array_intersect_key($data, array_flip($allowed));

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

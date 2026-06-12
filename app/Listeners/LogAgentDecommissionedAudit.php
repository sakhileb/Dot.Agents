<?php

namespace App\Listeners;

use App\Events\AgentDecommissioned;
use App\Jobs\SendPlatformNotification;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogAgentDecommissionedAudit implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(AgentDecommissioned $event): void
    {
        $deployment = $event->deployment;

        $this->auditService->logUserAction(
            event: 'agent.decommissioned',
            description: "Deployment {$deployment->display_name} was decommissioned. Reason: {$event->reason}",
            subject: $deployment,
            metadata: ['reason' => $event->reason],
        );

        SendPlatformNotification::toAdmins(
            organizationId: $deployment->organization_id,
            type: 'agent_decommissioned',
            title: "Agent Decommissioned: {$deployment->display_name}",
            message: $event->reason ?: 'The deployment has been permanently decommissioned.',
            severity: 'error',
            data: ['deployment_id' => $deployment->id, 'reason' => $event->reason],
            actionUrl: '/agents'
        );
    }

    public function failed(AgentDecommissioned $event, Throwable $exception): void
    {
        Log::error('[LogAgentDecommissionedAudit] Failed', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

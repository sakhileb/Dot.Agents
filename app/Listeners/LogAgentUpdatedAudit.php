<?php

namespace App\Listeners;

use App\Events\AgentUpdated;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogAgentUpdatedAudit implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(AgentUpdated $event): void
    {
        $deployment = $event->deployment;

        $this->auditService->logUserAction(
            event: 'agent.updated',
            description: "Deployment {$deployment->display_name} configuration updated",
            subject: $deployment,
            metadata: ['changes' => $event->changes],
        );
    }

    public function failed(AgentUpdated $event, Throwable $exception): void
    {
        Log::warning('[LogAgentUpdatedAudit] Failed', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

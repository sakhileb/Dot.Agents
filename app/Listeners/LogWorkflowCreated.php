<?php

namespace App\Listeners;

use App\Events\WorkflowCreated;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogWorkflowCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(WorkflowCreated $event): void
    {
        $workflow = $event->workflow;

        $this->auditService->logUserAction(
            event: 'workflow.created',
            description: "Workflow '{$workflow->name}' created (trigger: {$workflow->trigger_type})",
            subject: $workflow,
        );
    }

    public function failed(WorkflowCreated $event, Throwable $exception): void
    {
        Log::warning('[LogWorkflowCreated] Failed to log workflow creation audit', [
            'workflow_id' => $event->workflow->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

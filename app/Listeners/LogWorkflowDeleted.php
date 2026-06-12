<?php

namespace App\Listeners;

use App\Events\WorkflowDeleted;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogWorkflowDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(WorkflowDeleted $event): void
    {
        $this->auditService->logUserAction(
            event: 'workflow.deleted',
            description: "Workflow '{$event->workflowName}' (id:{$event->workflowId}) deleted",
            data: [
                'workflow_id' => $event->workflowId,
                'organization_id' => $event->organizationId,
                'workflow_name' => $event->workflowName,
            ],
        );
    }

    public function failed(WorkflowDeleted $event, Throwable $exception): void
    {
        Log::warning('[LogWorkflowDeleted] Failed to log workflow deletion audit', [
            'workflow_id' => $event->workflowId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\AgentTaskFailed;
use App\Jobs\RunDigitalImmuneSystemCheck;
use App\Models\AgentTask;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleAgentTaskFailed implements ShouldQueue
{
    public string $queue = 'governance';

    public function handle(AgentTaskFailed $event): void
    {
        $task = $event->task;
        $errorMessage = $event->reason;

        Log::warning('AgentTask failed', [
            'task_id' => $task->id,
            'deployment_id' => $task->agent_deployment_id,
            'error' => $errorMessage,
        ]);

        // Log failure in audit trail
        app(AuditService::class)->logUserAction(
            event: 'agent_task.failed',
            description: "Task #{$task->id} failed: {$errorMessage}",
            subject: $task,
        );

        // Increment failure counter on the deployment
        $deployment = $task->deployment;

        if (! $deployment) {
            return;
        }

        // If too many consecutive failures, flag the deployment for DIS review
        $recentFailures = AgentTask::where('agent_deployment_id', $deployment->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures >= 3) {
            Log::error('AgentDeployment: 3+ failures in 1 hour, triggering DIS check', [
                'deployment_id' => $deployment->id,
            ]);

            RunDigitalImmuneSystemCheck::dispatch()
                ->onQueue('governance');
        }
    }
}

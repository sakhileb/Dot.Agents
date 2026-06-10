<?php

namespace App\Listeners;

use App\Actions\Skills\RecordSkillScoreAction;
use App\Events\SkillExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordSkillScoreOnExecution implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly RecordSkillScoreAction $scoreAction
    ) {}

    public function handle(SkillExecuted $event): void
    {
        $execution = $event->execution;

        $this->scoreAction->execute(
            skillId: $execution->skill_id,
            deploymentId: $execution->agent_deployment_id,
            organizationId: $execution->organization_id,
            executionStatus: $execution->status,
            confidence: $execution->confidence ? (float) $execution->confidence : null,
            durationMs: $execution->duration_ms,
        );
    }

    public function failed(SkillExecuted $event, Throwable $exception): void
    {
        Log::warning('[RecordSkillScoreOnExecution] Failed to record skill score', [
            'execution_id' => $event->execution->id,
            'skill_id' => $event->execution->skill_id,
            'error' => $exception->getMessage(),
        ]);
    }
}

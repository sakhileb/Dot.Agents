<?php

namespace App\Listeners;

use App\Events\SkillAssigned;
use App\Services\Governance\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSkillAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(SkillAssigned $event): void
    {
        $assignment = $event->assignment;

        $this->auditService->logUserAction(
            event: 'skill.assigned',
            description: "Skill (id:{$assignment->skill_id}) assigned to deployment (id:{$assignment->agent_deployment_id})",
            subject: $assignment,
            metadata: [
                'skill_id' => $assignment->skill_id,
                'deployment_id' => $assignment->agent_deployment_id,
                'is_enabled' => $assignment->is_enabled,
            ],
        );
    }

    public function failed(SkillAssigned $event, Throwable $exception): void
    {
        Log::warning('[LogSkillAssigned] Failed to log skill assignment audit', [
            'assignment_id' => $event->assignment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

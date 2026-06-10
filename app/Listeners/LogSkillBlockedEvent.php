<?php

namespace App\Listeners;

use App\Events\SkillExecutionBlocked;
use App\Models\AgentSkill;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSkillBlockedEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'governance';

    public int $tries = 3;

    public function handle(SkillExecutionBlocked $event): void
    {
        AuditLog::create([
            'organization_id' => $event->organizationId,
            'actor_type' => 'agent',
            'actor_id' => $event->deployment->id,
            'event' => 'skill.execution.blocked',
            'auditable_type' => AgentSkill::class,
            'auditable_id' => $event->skill->id,
            'old_values' => null,
            'new_values' => [
                'skill' => $event->skill->name,
                'reason' => $event->reason,
                'deployment' => $event->deployment->name,
            ],
            'ip_address' => null,
            'user_agent' => null,
            'risk_level' => $event->skill->risk_level,
            'is_sensitive' => true,
        ]);
    }

    public function failed(SkillExecutionBlocked $event, Throwable $exception): void
    {
        Log::error('[LogSkillBlockedEvent] Failed to write audit log', [
            'skill_id' => $event->skill->id,
            'organization_id' => $event->organizationId,
            'error' => $exception->getMessage(),
        ]);
    }
}

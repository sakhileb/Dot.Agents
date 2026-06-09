<?php

namespace App\Skills\Governance;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Services\Governance\AuditService;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Audit Logging Skill (Layer 4 — Self-Governance)
 *
 * Records a structured audit entry for any skill or task execution.
 * Wraps the platform's AuditService with skill-aware context so that
 * every agent action produces a verifiable, governance-grade trail.
 */
class AuditLoggingSkill extends BaseSkill
{
    public function __construct(private readonly AuditService $auditService) {}

    public function key(): string
    {
        return 'audit-logging';
    }

    public function layer(): string
    {
        return 'governance';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        /** @var AgentDeployment|null $deployment */
        $deployment = $context['deployment'] ?? null;
        $task = $context['task'] ?? null;
        $phase = $context['phase'] ?? 'on_demand';
        $event = $input['event'] ?? 'skill.audit_log';
        $description = $input['description'] ?? 'Audit log recorded by AuditLoggingSkill';

        $metadata = array_merge(
            [
                'phase' => $phase,
                'task_id' => $task instanceof AgentTask ? $task->id : ($task['id'] ?? null),
                'skill' => $this->key(),
                'logged_at' => now()->toIso8601String(),
            ],
            $input['metadata'] ?? []
        );

        if ($deployment instanceof AgentDeployment) {
            $this->auditService->logAgentAction($deployment, $event, $metadata);
        }

        return SkillResult::completed(
            [
                'event' => $event,
                'logged_at' => now()->toIso8601String(),
                'phase' => $phase,
                'metadata' => $metadata,
            ],
            100.0
        );
    }
}

<?php

namespace App\Skills\Workforce;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;
use Illuminate\Support\Str;

/**
 * Delegation Skill (Layer 3 — Workforce)
 *
 * Accepts a list of subtasks (produced by TaskDecompositionSkill) and
 * assigns each to the most appropriate active AgentDeployment within the
 * organisation that has the required skill enabled.
 *
 * Each successful assignment creates an AgentTask record (status: pending)
 * so the execution history is fully audited.
 */
class DelegationSkill extends BaseSkill
{
    public function key(): string
    {
        return 'delegation';
    }

    public function layer(): string
    {
        return 'workforce';
    }

    /**
     * Input keys:
     *   subtasks    – array of subtask definitions (from TaskDecompositionSkill)
     *   parent_task – string description of the parent task (for audit context)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $subtasks = $input['subtasks'] ?? [];
        $parentTask = $input['parent_task'] ?? 'Delegated task';
        $deployment = $context['deployment'] ?? null;

        if (empty($subtasks)) {
            return SkillResult::skipped('No subtasks provided for delegation');
        }

        $organizationId = $deployment instanceof AgentDeployment
            ? $deployment->organization_id
            : (int) session('current_organization_id');

        $assignments = [];
        $assignedCount = 0;
        $unassigned = 0;

        foreach ($subtasks as $subtask) {
            $requiredSkill = $subtask['required_skill'] ?? null;
            $targetDeploy = $this->findDeploymentWithSkill($requiredSkill, $organizationId);

            if ($targetDeploy) {
                $task = AgentTask::create([
                    'uuid' => (string) Str::uuid(),
                    'agent_deployment_id' => $targetDeploy->id,
                    'organization_id' => $organizationId,
                    'title' => $subtask['description'] ?? "Subtask: {$subtask['key']}",
                    'description' => $subtask['description'] ?? 'Delegated from workforce orchestration',
                    'task_type' => 'delegated',
                    'priority' => $subtask['priority'] ?? 'medium',
                    'status' => 'pending',
                    'input_data' => [
                        'parent_task' => $parentTask,
                        'subtask' => $subtask,
                        'delegated_from' => $deployment?->id,
                    ],
                ]);

                $assignments[] = [
                    'subtask_key' => $subtask['key'] ?? 'unknown',
                    'task_id' => $task->id,
                    'assigned_to_id' => $targetDeploy->id,
                    'assigned_agent' => $targetDeploy->display_name,
                    'required_skill' => $requiredSkill,
                    'status' => 'assigned',
                ];
                $assignedCount++;
            } else {
                $assignments[] = [
                    'subtask_key' => $subtask['key'] ?? 'unknown',
                    'task_id' => null,
                    'assigned_to_id' => null,
                    'assigned_agent' => null,
                    'required_skill' => $requiredSkill,
                    'status' => 'unassigned',
                    'reason' => "No active deployment with skill [{$requiredSkill}] found in organisation #{$organizationId}",
                ];
                $unassigned++;
            }
        }

        $findings = $unassigned > 0
            ? ["{$unassigned} subtask(s) could not be assigned — no qualifying agents found"]
            : [];

        return SkillResult::completed(
            [
                'assignments' => $assignments,
                'assigned_agents_count' => $assignedCount,
                'total_subtasks' => count($subtasks),
                'unassigned_count' => $unassigned,
                'fully_delegated' => $unassigned === 0,
            ],
            $assignedCount > 0 ? 90.0 : 40.0,
            $findings
        );
    }

    // ── Internal ─────────────────────────────────────────

    private function findDeploymentWithSkill(string $skillKey, int $organizationId): ?AgentDeployment
    {
        return AgentDeployment::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereHas(
                'skillAssignments',
                fn ($q) => $q->where('is_enabled', true)
                    ->whereHas('skill', fn ($sq) => $sq->where('key', $skillKey))
            )
            ->first();
    }
}

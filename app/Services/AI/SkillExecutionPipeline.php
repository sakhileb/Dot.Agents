<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillExecution;
use App\Models\AgentTask;
use App\Skills\Contracts\SkillContract;
use App\Skills\DTOs\SkillResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Skill Execution Pipeline
 *
 * Wraps every agent task execution with the appropriate skill invocations:
 *
 *   PRE-EXECUTION  → RiskAssessment, SelfVerification (gate checks)
 *   ORCHESTRATION  → WorkforceOrchestration (can I handle this? or delegate?)
 *   POST-EXECUTION → ConfidenceScoring, AuditLogging, AgentEvaluator
 *
 * Each invocation is recorded to agent_skill_executions for a complete audit trail.
 * Errors in individual skills are caught and logged — they never block task execution.
 */
class SkillExecutionPipeline
{
    public function __construct(
        private readonly SkillRegistryService $registry,
    ) {}

    // ── Pipeline phases ──────────────────────────────────

    /**
     * Run pre-execution skills and return their combined results.
     * These run BEFORE task execution as quality gates.
     */
    public function runPreExecution(AgentDeployment $deployment, AgentTask $task): array
    {
        return $this->runSkillSet(
            ['risk-assessment', 'self-verification'],
            [
                'task_description' => $task->description,
                'task' => $task->toArray(),
            ],
            $deployment,
            $task,
            'pre_task'
        );
    }

    /**
     * Run post-execution skills and return their combined results.
     * These run AFTER task execution to score and log output.
     */
    public function runPostExecution(AgentDeployment $deployment, AgentTask $task, array $taskOutput): array
    {
        return $this->runSkillSet(
            ['confidence-scoring', 'audit-logging', 'agent-evaluator'],
            [
                'output' => $taskOutput,
                'task_description' => $task->description,
                'task_id' => $task->id,
                'task' => $task->toArray(),
            ],
            $deployment,
            $task,
            'post_task'
        );
    }

    /**
     * Run WorkforceOrchestration to decide: execute self vs delegate.
     * Returns a SkillResult with decision = 'execute' | 'delegated' | 'execute_with_caution'
     */
    public function runOrchestration(
        AgentDeployment $deployment,
        AgentTask $task,
        float $initialConfidence
    ): SkillResult {
        if (! $this->registry->deploymentHasSkill($deployment, 'workforce-orchestration')) {
            // No orchestration skill assigned — default to direct execution
            return SkillResult::completed(
                ['decision' => 'execute', 'reason' => 'Workforce orchestration skill not assigned'],
                $initialConfidence
            );
        }

        return $this->invokeSkill(
            'workforce-orchestration',
            [
                'task_description' => $task->description,
                'initial_confidence' => $initialConfidence,
            ],
            $deployment,
            $task,
            'pre_task'
        );
    }

    // ── Internal helpers ─────────────────────────────────

    /**
     * Invoke a set of skills by key, skipping those not enabled on the deployment.
     * Returns array keyed by skill key.
     */
    private function runSkillSet(
        array $skillKeys,
        array $input,
        AgentDeployment $deployment,
        AgentTask $task,
        string $trigger
    ): array {
        $results = [];

        foreach ($skillKeys as $key) {
            if (! $this->registry->deploymentHasSkill($deployment, $key)) {
                continue;
            }

            $results[$key] = $this->invokeSkill($key, $input, $deployment, $task, $trigger)->toArray();
        }

        return $results;
    }

    private function invokeSkill(
        string $key,
        array $input,
        AgentDeployment $deployment,
        AgentTask $task,
        string $trigger
    ): SkillResult {
        $startMs = (int) (microtime(true) * 1000);

        try {
            if (! $this->registry->hasImplementation($key)) {
                return SkillResult::skipped("Skill [{$key}] has no PHP implementation");
            }

            $skill = $this->registry->resolve($key);
            $result = $skill->execute($input, ['deployment' => $deployment, 'task' => $task, 'phase' => $trigger]);

            $this->recordExecution($skill, $deployment, $task, $result, $trigger, (int) (microtime(true) * 1000) - $startMs);

            return $result;
        } catch (Throwable $e) {
            Log::warning("[SkillPipeline] Skill [{$key}] failed", [
                'deployment_id' => $deployment->id,
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return SkillResult::failed($e->getMessage());
        }
    }

    /** Persist execution record to agent_skill_executions. */
    private function recordExecution(
        SkillContract $skill,
        AgentDeployment $deployment,
        AgentTask $task,
        SkillResult $result,
        string $trigger,
        int $durationMs
    ): void {
        try {
            $skillModel = AgentSkill::where('key', $skill->key())->first();
            if (! $skillModel) {
                return;
            }

            AgentSkillExecution::create([
                'uuid' => (string) Str::uuid(),
                'skill_id' => $skillModel->id,
                'agent_deployment_id' => $deployment->id,
                'organization_id' => $deployment->organization_id,
                'task_id' => $task->id,
                'trigger' => $trigger,
                'status' => $result->status,
                'input' => ['task_id' => $task->id],
                'output' => $result->output,
                'findings' => $result->findings ?: null,
                'confidence' => $result->confidence,
                'duration_ms' => $durationMs,
                'executed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning("[SkillPipeline] Failed to record execution for skill [{$skill->key()}]", ['error' => $e->getMessage()]);
        }
    }
}

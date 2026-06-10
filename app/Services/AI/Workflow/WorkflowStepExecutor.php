<?php

namespace App\Services\AI\Workflow;

use App\Jobs\SendPlatformNotification;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\WorkflowExecution;
use App\Services\AI\AgentOrchestrationService;
use Illuminate\Support\Str;
use Throwable;

/**
 * WorkflowStepExecutor
 *
 * Handles the per-step execution logic for WorkflowEngineService.
 *
 * Supports three step types:
 *  - agent_task      → dispatches an AgentTask via AgentOrchestrationService
 *  - notification    → fires a queued SendPlatformNotification job
 *  - data_transform  → performs dot-notation key mapping on previous results
 *
 * Also evaluates step conditions and collects step outputs.
 *
 * Extracted from WorkflowEngineService.
 */
class WorkflowStepExecutor
{
    public function __construct(
        private readonly AgentOrchestrationService $orchestrator,
    ) {}

    /**
     * Execute a single workflow step and return its result array.
     */
    public function executeStep(array $step, WorkflowExecution $execution, array $previousResults): array
    {
        $type = $step['type'] ?? 'agent_task';

        return match ($type) {
            'agent_task'     => $this->runAgentTaskStep($step, $execution),
            'notification'   => $this->runNotificationStep($step, $execution),
            'data_transform' => $this->runDataTransformStep($step, $previousResults),
            default          => ['status' => 'skipped', 'reason' => "Unknown step type: {$type}"],
        };
    }

    /**
     * Evaluate a step's condition against previous step results.
     * Returns true if the step should run, false if it should be skipped.
     */
    public function evaluateCondition(array $step, array $previousResults): bool
    {
        $condition = $step['condition'] ?? null;
        if (! $condition) {
            return true;
        }

        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $parts = explode('.', $field);
        $actual = $previousResults;
        foreach ($parts as $part) {
            $actual = is_array($actual) ? ($actual[$part] ?? null) : null;
        }

        return match ($operator) {
            '==' => $actual == $value,
            '!=' => $actual != $value,
            '>'  => $actual > $value,
            '>=' => $actual >= $value,
            '<'  => $actual < $value,
            '<=' => $actual <= $value,
            'in' => in_array($actual, (array) $value),
            default => true,
        };
    }

    /**
     * Collect 'output' values from all completed step results.
     *
     * @return array<string, mixed>
     */
    public function collectOutputs(array $stepResults): array
    {
        $outputs = [];
        foreach ($stepResults as $i => $result) {
            if (isset($result['output'])) {
                $outputs["step_{$i}"] = $result['output'];
            }
        }

        return $outputs;
    }

    // ── Step type runners ─────────────────────────────────────────────────────

    private function runAgentTaskStep(array $step, WorkflowExecution $execution): array
    {
        $deploymentId = $step['agent_deployment_id'] ?? null;
        if (! $deploymentId) {
            return ['status' => 'failed', 'error' => 'No agent_deployment_id configured for step'];
        }

        $deployment = AgentDeployment::find($deploymentId);
        if (! $deployment || $deployment->status !== 'active') {
            return ['status' => 'failed', 'error' => "Agent deployment #{$deploymentId} not active"];
        }

        $task = AgentTask::create([
            'uuid' => (string) Str::uuid(),
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $execution->organization_id,
            'title' => $step['title'] ?? 'Workflow Step Task',
            'description' => $step['description'] ?? '',
            'task_type' => $step['task_type'] ?? 'workflow',
            'priority' => $step['priority'] ?? 'medium',
            'status' => 'pending',
            'input_data' => array_merge($execution->input_data ?? [], $step['input_override'] ?? []),
        ]);

        try {
            $completedTask = $this->orchestrator->executeTask($deployment, $task);

            return [
                'status' => $completedTask->status,
                'task_id' => $completedTask->id,
                'output' => $completedTask->output_data,
                'confidence' => $completedTask->confidence_score,
            ];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'task_id' => $task->id, 'error' => $e->getMessage()];
        }
    }

    private function runNotificationStep(array $step, WorkflowExecution $execution): array
    {
        SendPlatformNotification::toAdmins(
            organizationId: $execution->organization_id,
            type: 'workflow_notification',
            title: $step['title'] ?? 'Workflow Update',
            message: $step['message'] ?? 'A workflow step completed.',
            severity: $step['severity'] ?? 'info',
            data: ['execution_id' => $execution->id]
        );

        return ['status' => 'completed'];
    }

    private function runDataTransformStep(array $step, array $previousResults): array
    {
        $output = [];
        $mapping = $step['output_mapping'] ?? [];

        foreach ($mapping as $targetKey => $sourceExpression) {
            $parts = explode('.', $sourceExpression);
            $value = $previousResults;
            foreach ($parts as $part) {
                $value = is_array($value) ? ($value[$part] ?? null) : null;
            }
            $output[$targetKey] = $value;
        }

        return ['status' => 'completed', 'output' => $output];
    }
}

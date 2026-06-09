<?php

namespace App\Services\AI;

use App\Jobs\SendPlatformNotification;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\AgentWorkflow;
use App\Models\WorkflowExecution;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Workflow Engine Service
 *
 * Executes AgentWorkflows step by step, tracks execution state,
 * handles conditional branching, approval gates, and failures.
 */
class WorkflowEngineService
{
    public function __construct(
        private readonly AgentOrchestrationService $orchestrator,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Start executing a workflow, returning the execution record.
     */
    public function start(AgentWorkflow $workflow, array $inputData = [], ?int $triggeredBy = null): WorkflowExecution
    {
        $execution = WorkflowExecution::create([
            'uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'organization_id' => $workflow->organization_id,
            'triggered_by' => $triggeredBy,
            'trigger_type' => $workflow->trigger_type,
            'status' => 'running',
            'current_step' => 0,
            'input_data' => $inputData,
            'step_results' => [],
            'started_at' => now(),
        ]);

        $this->auditService->logUserAction(
            event: 'workflow.started',
            description: "Workflow '{$workflow->name}' execution started",
            subject: $workflow,
            metadata: ['execution_id' => $execution->id]
        );

        try {
            $this->runSteps($workflow, $execution);
        } catch (Throwable $e) {
            $this->failExecution($execution, $e->getMessage());
            Log::error('[WorkflowEngine] Execution failed', [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $execution->refresh();
    }

    /**
     * Resume a paused workflow execution from the current step.
     */
    public function resume(WorkflowExecution $execution, array $resumeData = []): WorkflowExecution
    {
        if ($execution->status !== 'paused') {
            throw new \RuntimeException("Execution #{$execution->id} is not paused (status: {$execution->status}).");
        }

        $execution->update([
            'status' => 'running',
            'resumed_data' => $resumeData,
        ]);

        $workflow = $execution->workflow;

        try {
            $this->runSteps($workflow, $execution);
        } catch (Throwable $e) {
            $this->failExecution($execution, $e->getMessage());
        }

        return $execution->refresh();
    }

    /**
     * Abort a running or paused execution.
     */
    public function abort(WorkflowExecution $execution, string $reason = 'Manually aborted'): void
    {
        $execution->update([
            'status' => 'failed',
            'error_message' => $reason,
            'completed_at' => now(),
        ]);

        $this->auditService->logUserAction(
            event: 'workflow.aborted',
            description: "Workflow execution #{$execution->id} aborted",
            subject: $execution,
            metadata: ['reason' => $reason]
        );
    }

    /**
     * Run all pending steps in order from the current step index.
     */
    private function runSteps(AgentWorkflow $workflow, WorkflowExecution $execution): void
    {
        $steps = $workflow->steps ?? [];
        $currentStep = $execution->current_step ?? 0;
        $stepResults = $execution->step_results ?? [];

        for ($i = $currentStep; $i < count($steps); $i++) {
            $step = $steps[$i];

            // Evaluate conditional logic
            if (! $this->evaluateCondition($step, $stepResults)) {
                $stepResults[$i] = ['status' => 'skipped', 'reason' => 'condition_not_met'];
                $execution->update(['step_results' => $stepResults, 'current_step' => $i + 1]);

                continue;
            }

            // Handle approval gate steps
            if (($step['type'] ?? '') === 'approval_gate') {
                $execution->update([
                    'status' => 'paused',
                    'current_step' => $i,
                    'step_results' => $stepResults,
                ]);

                return; // pause and wait for manual resume
            }

            // Execute the step
            $result = $this->executeStep($step, $execution, $stepResults);
            $stepResults[$i] = $result;

            $execution->update([
                'current_step' => $i + 1,
                'step_results' => $stepResults,
            ]);

            // Stop if a step hard-failed
            if ($result['status'] === 'failed' && ($step['halt_on_failure'] ?? true)) {
                $this->failExecution($execution, "Step {$i} failed: ".($result['error'] ?? 'Unknown'));

                return;
            }
        }

        // All steps complete
        $execution->update([
            'status' => 'completed',
            'output_data' => $this->collectOutputs($stepResults),
            'completed_at' => now(),
        ]);

        $this->auditService->logUserAction(
            event: 'workflow.completed',
            description: "Workflow '{$workflow->name}' completed successfully",
            subject: $workflow,
            metadata: ['execution_id' => $execution->id, 'steps_run' => count($steps)]
        );
    }

    /**
     * Execute a single workflow step.
     */
    private function executeStep(array $step, WorkflowExecution $execution, array $previousResults): array
    {
        $type = $step['type'] ?? 'agent_task';

        return match ($type) {
            'agent_task' => $this->runAgentTaskStep($step, $execution),
            'notification' => $this->runNotificationStep($step, $execution),
            'data_transform' => $this->runDataTransformStep($step, $previousResults),
            default => ['status' => 'skipped', 'reason' => "Unknown step type: {$type}"],
        };
    }

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
        // Simple pass-through with optional key mapping
        $output = [];
        $mapping = $step['output_mapping'] ?? [];

        foreach ($mapping as $targetKey => $sourceExpression) {
            // Support dot-notation like "step_0.output.summary"
            $parts = explode('.', $sourceExpression);
            $value = $previousResults;
            foreach ($parts as $part) {
                $value = is_array($value) ? ($value[$part] ?? null) : null;
            }
            $output[$targetKey] = $value;
        }

        return ['status' => 'completed', 'output' => $output];
    }

    private function evaluateCondition(array $step, array $previousResults): bool
    {
        $condition = $step['condition'] ?? null;
        if (! $condition) {
            return true; // no condition = always run
        }

        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        // Resolve value from previous step results via dot notation
        $parts = explode('.', $field);
        $actual = $previousResults;
        foreach ($parts as $part) {
            $actual = is_array($actual) ? ($actual[$part] ?? null) : null;
        }

        return match ($operator) {
            '==' => $actual == $value,
            '!=' => $actual != $value,
            '>' => $actual > $value,
            '>=' => $actual >= $value,
            '<' => $actual < $value,
            '<=' => $actual <= $value,
            'in' => in_array($actual, (array) $value),
            default => true,
        };
    }

    private function collectOutputs(array $stepResults): array
    {
        $outputs = [];
        foreach ($stepResults as $i => $result) {
            if (isset($result['output'])) {
                $outputs["step_{$i}"] = $result['output'];
            }
        }

        return $outputs;
    }

    private function failExecution(WorkflowExecution $execution, string $reason): void
    {
        $execution->update([
            'status' => 'failed',
            'error_message' => $reason,
            'completed_at' => now(),
        ]);
    }
}

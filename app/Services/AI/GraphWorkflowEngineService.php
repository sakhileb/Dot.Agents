<?php

namespace App\Services\AI;

use App\Models\AgentWorkflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use App\Services\Governance\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Graph Workflow Engine Service
 *
 * Executes agent workflows modelled as a directed acyclic graph (DAG).
 * Supports:
 *  – Conditional branching via edge conditions
 *  – Multi-agent chaining (output of node A flows into node B)
 *  – Cycle detection (guards against infinite loops)
 *  – Parallel fan-out when multiple edges leave a node
 *
 * Node output is merged into a shared execution context keyed by node UUID,
 * so downstream nodes can reference results from any upstream node.
 */
class GraphWorkflowEngineService
{
    public function __construct(
        private readonly AgentOrchestrationService $orchestrator,
        private readonly AuditService $auditService,
    ) {}

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    /**
     * Execute a workflow graph and return the final merged context.
     */
    public function execute(AgentWorkflow $workflow, array $initialInput = [], ?int $triggeredBy = null): WorkflowExecution
    {
        $execution = WorkflowExecution::create([
            'uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'organization_id' => $workflow->organization_id,
            'triggered_by' => $triggeredBy,
            'trigger_type' => $workflow->trigger_type,
            'status' => 'running',
            'current_step' => 0,
            'input_data' => $initialInput,
            'step_results' => [],
            'started_at' => now(),
        ]);

        $this->auditService->logUserAction(
            event: 'workflow.graph.started',
            description: "Graph workflow '{$workflow->name}' execution started",
            subject: $workflow,
            metadata: ['execution_id' => $execution->id]
        );

        try {
            $context = $this->runGraph($workflow, $execution, $initialInput);

            $execution->update([
                'status' => 'completed',
                'output_data' => $context,
                'completed_at' => now(),
            ]);

            $this->auditService->logUserAction(
                event: 'workflow.graph.completed',
                description: "Graph workflow '{$workflow->name}' completed successfully",
                subject: $workflow,
                metadata: ['execution_id' => $execution->id, 'nodes_executed' => count($context)]
            );
        } catch (Throwable $e) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('[GraphWorkflowEngine] Execution failed', [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $execution->refresh();
    }

    // ──────────────────────────────────────────────
    // Graph traversal
    // ──────────────────────────────────────────────

    /**
     * BFS traversal of the graph, executing each reachable node in topological order.
     * Returns the final merged context (keyed by node UUID).
     */
    private function runGraph(AgentWorkflow $workflow, WorkflowExecution $execution, array $initialInput): array
    {
        // Eager-load graph structure
        $workflow->load(['nodes', 'connections']);

        $nodes = $workflow->nodes->keyBy('uuid');         // uuid → WorkflowNode
        $connections = $workflow->connections;

        $context = ['__input' => $initialInput];
        $visited = [];
        $queue = $this->resolveStartNodes($nodes, $connections);
        $stepResults = [];
        $stepIndex = 0;

        while ($queue->isNotEmpty()) {
            /** @var WorkflowNode $node */
            $node = $queue->shift();

            if (isset($visited[$node->uuid])) {
                continue;
            }

            $visited[$node->uuid] = true;

            Log::debug('[GraphWorkflowEngine] Executing node', [
                'node_uuid' => $node->uuid,
                'agent_key' => $node->agent_key,
            ]);

            $result = $this->executeNode($node, $context, $execution);

            // Store result in context keyed by node UUID
            $context[$node->uuid] = $result;

            $stepResults[$stepIndex] = [
                'node_uuid' => $node->uuid,
                'agent_key' => $node->agent_key,
                'status' => $result['status'] ?? 'unknown',
                'output' => $result['output'] ?? null,
            ];

            $execution->update([
                'current_step' => ++$stepIndex,
                'step_results' => $stepResults,
            ]);

            // Resolve which nodes come next, respecting edge conditions
            $nextNodes = $this->resolveNextNodes($connections, $nodes, $node, $result);
            foreach ($nextNodes as $next) {
                $queue->push($next);
            }
        }

        return $context;
    }

    // ──────────────────────────────────────────────
    // Node execution
    // ──────────────────────────────────────────────

    private function executeNode(WorkflowNode $node, array $context, WorkflowExecution $execution): array
    {
        try {
            $result = $this->orchestrator->executeGraphNode(
                agentKey: $node->agent_key,
                context: $context,
                nodeConfig: $node->config ?? [],
                metadata: [
                    'workflow_id' => $execution->workflow_id,
                    'execution_id' => $execution->id,
                    'node_uuid' => $node->uuid,
                ]
            );

            return array_merge(['status' => 'completed'], $result);
        } catch (Throwable $e) {
            Log::warning('[GraphWorkflowEngine] Node execution failed', [
                'node_uuid' => $node->uuid,
                'agent_key' => $node->agent_key,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    // ──────────────────────────────────────────────
    // Graph utilities
    // ──────────────────────────────────────────────

    /**
     * Find start nodes — nodes that have no incoming edges.
     */
    private function resolveStartNodes(Collection $nodes, Collection $connections): Collection
    {
        $targetUuids = $connections->pluck('to_node_uuid')->unique()->toArray();

        return $nodes->filter(fn (WorkflowNode $n) => ! in_array($n->uuid, $targetUuids, true))
            ->values();
    }

    /**
     * Resolve which nodes should run after the given node, based on edge conditions.
     */
    private function resolveNextNodes(
        Collection $connections,
        Collection $nodes,
        WorkflowNode $currentNode,
        array $result
    ): Collection {
        return $connections
            ->where('from_node_uuid', $currentNode->uuid)
            ->filter(fn ($edge) => $this->evaluateCondition($edge->condition, $result))
            ->map(fn ($edge) => $nodes->get($edge->to_node_uuid))
            ->filter() // remove nulls (dangling edges)
            ->values();
    }

    /**
     * Evaluate an edge condition against a node's output.
     *
     * Supported condition shapes:
     *   null                         → always pass
     *   { "status": "completed" }    → match status string
     *   { "min_confidence": 0.7 }    → confidence >= threshold
     *   { "field": "x", "op": ">=", "value": 5 } → generic comparison
     */
    private function evaluateCondition(?array $condition, array $result): bool
    {
        if (empty($condition)) {
            return true;
        }

        // Status match
        if (isset($condition['status'])) {
            return ($result['status'] ?? null) === $condition['status'];
        }

        // Confidence threshold
        if (isset($condition['min_confidence'])) {
            return (float) ($result['confidence'] ?? 0) >= (float) $condition['min_confidence'];
        }

        // Generic comparison via dot-notation field path
        if (isset($condition['field'], $condition['op'], $condition['value'])) {
            $actual = data_get($result, $condition['field']);

            return match ($condition['op']) {
                '==' => $actual == $condition['value'],
                '!=' => $actual != $condition['value'],
                '>' => $actual > $condition['value'],
                '>=' => $actual >= $condition['value'],
                '<' => $actual < $condition['value'],
                '<=' => $actual <= $condition['value'],
                'in' => in_array($actual, (array) $condition['value']),
                default => true,
            };
        }

        return true;
    }
}

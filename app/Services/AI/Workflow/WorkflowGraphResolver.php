<?php

namespace App\Services\AI\Workflow;

use App\Models\WorkflowNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * WorkflowGraphResolver
 *
 * Handles the graph topology utilities for the GraphWorkflowEngineService:
 *  - Identifying start nodes (nodes with no incoming edges)
 *  - Resolving next nodes after a given node (respecting edge conditions)
 *  - Evaluating edge conditions against node output
 *  - Enforcing per-organization execution rate limits
 *
 * Extracted from GraphWorkflowEngineService.
 */
class WorkflowGraphResolver
{
    /** Max concurrent workflow executions per organization per minute. */
    private const MAX_EXECUTIONS_PER_ORG_PER_MINUTE = 20;

    /**
     * Find start nodes — nodes that have no incoming edges.
     *
     * @param  Collection<string, WorkflowNode>  $nodes  UUID-keyed node collection
     * @param  Collection  $connections  Edge collection
     * @return Collection<WorkflowNode>
     */
    public function resolveStartNodes(Collection $nodes, Collection $connections): Collection
    {
        $targetUuids = $connections->pluck('to_node_uuid')->unique()->toArray();

        return $nodes->filter(fn (WorkflowNode $n) => ! in_array($n->uuid, $targetUuids, true))
            ->values();
    }

    /**
     * Resolve which nodes should run after the given node, based on edge conditions.
     *
     * @return Collection<WorkflowNode>
     */
    public function resolveNextNodes(
        Collection $connections,
        Collection $nodes,
        WorkflowNode $currentNode,
        array $result
    ): Collection {
        return $connections
            ->where('from_node_uuid', $currentNode->uuid)
            ->filter(fn ($edge) => $this->evaluateCondition($edge->condition, $result))
            ->map(fn ($edge) => $nodes->get($edge->to_node_uuid))
            ->filter()
            ->values();
    }

    /**
     * Evaluate an edge condition against a node's output.
     *
     * Supported condition shapes:
     *   null                                           → always pass
     *   { "status": "completed" }                     → match status string
     *   { "min_confidence": 0.7 }                     → confidence >= threshold
     *   { "field": "x", "op": ">=", "value": 5 }     → generic dot-notation comparison
     */
    public function evaluateCondition(?array $condition, array $result): bool
    {
        if (empty($condition)) {
            return true;
        }

        if (isset($condition['status'])) {
            return ($result['status'] ?? null) === $condition['status'];
        }

        if (isset($condition['min_confidence'])) {
            return (float) ($result['confidence'] ?? 0) >= (float) $condition['min_confidence'];
        }

        if (isset($condition['field'], $condition['op'], $condition['value'])) {
            $actual = data_get($result, $condition['field']);

            return match ($condition['op']) {
                '=='    => $actual == $condition['value'],
                '!='    => $actual != $condition['value'],
                '>'     => $actual > $condition['value'],
                '>='    => $actual >= $condition['value'],
                '<'     => $actual < $condition['value'],
                '<='    => $actual <= $condition['value'],
                'in'    => in_array($actual, (array) $condition['value']),
                default => true,
            };
        }

        return true;
    }

    /**
     * Enforce a per-organization workflow execution rate limit using a sliding window.
     *
     * @throws \RuntimeException if the rate limit is exceeded
     */
    public function enforceExecutionRateLimit(int $organizationId): void
    {
        $key = "workflow_rate_limit:{$organizationId}:".(int) (time() / 60);
        $count = (int) Cache::get($key, 0);

        if ($count >= self::MAX_EXECUTIONS_PER_ORG_PER_MINUTE) {
            Log::warning('[WorkflowGraphResolver] Org execution rate limit exceeded', [
                'organization_id' => $organizationId,
                'count_this_minute' => $count,
                'limit' => self::MAX_EXECUTIONS_PER_ORG_PER_MINUTE,
            ]);

            throw new \RuntimeException(
                "Workflow execution rate limit exceeded for organization [{$organizationId}]. "
                .'Max '.self::MAX_EXECUTIONS_PER_ORG_PER_MINUTE.' executions per minute.'
            );
        }

        Cache::put($key, $count + 1, 120);
    }
}

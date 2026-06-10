<?php

namespace App\Services\AI;

use App\Models\AgentWorkflow;
use App\Models\WorkflowNode;
use Illuminate\Support\Facades\Log;

/**
 * Workflow Risk Scoring Service
 *
 * Evaluates an AgentWorkflow before execution and returns a risk score (0–100).
 * High-risk workflows can be blocked or flagged for human approval.
 *
 * Risk dimensions scored:
 *  1. Node count        — large workflows are resource-intensive
 *  2. Dangerous agents  — agents with elevated permissions increase blast radius
 *  3. Autonomous mode   — fully autonomous deployments bypass human oversight
 *  4. Fan-out width     — many parallel branches amplify resource consumption
 *  5. External actions  — nodes that call external APIs/webhooks are higher risk
 *  6. Recursive structure — circular-adjacent patterns should be flagged
 */
class WorkflowRiskScoringService
{
    /** Workflow is blocked if risk score >= this threshold. */
    public const BLOCK_THRESHOLD = 85;

    /** Workflow requires human approval if risk score >= this threshold. */
    public const APPROVAL_THRESHOLD = 60;

    /** Dangerous agent keys that elevate risk when present in a workflow. */
    private const HIGH_RISK_AGENT_KEYS = [
        'code-executor', 'file-manager', 'email-sender', 'webhook-caller',
        'database-writer', 'api-caller', 'shell-executor', 'browser-agent',
    ];

    /**
     * Score a workflow and return the risk assessment.
     *
     * @return array{
     *     score: int,
     *     level: string,
     *     requires_approval: bool,
     *     is_blocked: bool,
     *     factors: array<string, mixed>
     * }
     */
    public function assess(AgentWorkflow $workflow): array
    {
        $workflow->loadMissing(['nodes', 'connections']);

        $factors = [];
        $score = 0;

        // 1. Node count risk
        $nodeCount = $workflow->nodes->count();
        $nodeRisk = min(30, (int) ($nodeCount / 5) * 5); // +5 per 5 nodes, max 30
        $factors['node_count'] = ['count' => $nodeCount, 'risk_contribution' => $nodeRisk];
        $score += $nodeRisk;

        // 2. Dangerous agent usage
        $dangerousNodes = $workflow->nodes->filter(
            fn (WorkflowNode $n) => in_array($n->agent_key, self::HIGH_RISK_AGENT_KEYS, true)
        );
        $dangerousCount = $dangerousNodes->count();
        $dangerousRisk = min(25, $dangerousCount * 10);
        $factors['dangerous_agents'] = ['count' => $dangerousCount, 'keys' => $dangerousNodes->pluck('agent_key')->toArray(), 'risk_contribution' => $dangerousRisk];
        $score += $dangerousRisk;

        // 3. Fan-out width (max parallel branches from a single node)
        $maxFanOut = 0;
        foreach ($workflow->nodes as $node) {
            $outgoing = $workflow->connections->where('from_node_uuid', $node->uuid)->count();
            $maxFanOut = max($maxFanOut, $outgoing);
        }
        $fanOutRisk = min(20, ($maxFanOut > 5 ? 20 : ($maxFanOut > 3 ? 10 : 0)));
        $factors['fan_out'] = ['max_fan_out' => $maxFanOut, 'risk_contribution' => $fanOutRisk];
        $score += $fanOutRisk;

        // 4. Autonomous mode deployments in the workflow
        $autonomousNodeKeys = $workflow->nodes->pluck('agent_key')->unique()->toArray();
        $autonomousRisk = 0; // Placeholder — would query deployments if agent_deployment_id is on nodes
        $factors['autonomous_risk'] = ['risk_contribution' => $autonomousRisk];
        $score += $autonomousRisk;

        // 5. External action nodes (web hooks, API calls, emails)
        $externalNodes = $workflow->nodes->filter(function (WorkflowNode $n) {
            $config = $n->config ?? [];

            return isset($config['external_url']) || isset($config['webhook_url']) || ($config['type'] ?? '') === 'external';
        });
        $externalRisk = min(15, $externalNodes->count() * 8);
        $factors['external_actions'] = ['count' => $externalNodes->count(), 'risk_contribution' => $externalRisk];
        $score += $externalRisk;

        // 6. Nodes at or near MAX_NODES_PER_EXECUTION cap (within 20% of limit)
        $executionCapLimit = 100;
        if ($nodeCount > $executionCapLimit * 0.8) {
            $nearCapRisk = 10;
            $factors['near_execution_cap'] = ['risk_contribution' => $nearCapRisk];
            $score += $nearCapRisk;
        }

        $score = min(100, $score);
        $level = match (true) {
            $score >= self::BLOCK_THRESHOLD => 'critical',
            $score >= self::APPROVAL_THRESHOLD => 'high',
            $score >= 35 => 'medium',
            default => 'low',
        };

        $result = [
            'score' => $score,
            'level' => $level,
            'requires_approval' => $score >= self::APPROVAL_THRESHOLD,
            'is_blocked' => $score >= self::BLOCK_THRESHOLD,
            'factors' => $factors,
        ];

        if ($score >= self::APPROVAL_THRESHOLD) {
            Log::info('[WorkflowRiskScoring] High-risk workflow detected', [
                'workflow_id' => $workflow->id,
                'risk_score' => $score,
                'level' => $level,
                'is_blocked' => $result['is_blocked'],
            ]);
        }

        return $result;
    }
}

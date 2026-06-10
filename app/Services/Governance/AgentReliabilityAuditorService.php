<?php

namespace App\Services\Governance;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\DecisionLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AgentReliabilityAuditorService
 *
 * Computes per-deployment and org-wide Agent Reliability Scores
 * across the six MEGA V2 Agent Reliability dimensions.
 *
 * MEGA V2 Domain: Agent Reliability (4% weight)
 * Production Gate: ≥ 90 required
 * Target: 95+
 *
 * Score Dimensions (per deployment):
 *  - Accuracy          (20 pts) — tasks without delusion flags / high-risk decisions
 *  - Consistency       (20 pts) — variance in confidence scores (low variance = good)
 *  - Completion Rate   (20 pts) — completed / (completed + failed + cancelled)
 *  - Success Rate      (15 pts) — tasks that met their objective (human-approved)
 *  - False Positive    (15 pts) — hallucinations flagged / total decisions
 *  - False Negative    (10 pts) — decisions that required correction after human review
 */
class AgentReliabilityAuditorService
{
    private const CACHE_TTL = 1800; // 30 minutes per deployment

    public function __construct(
        private readonly AgentReputationService $reputationService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Public interface
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calculate the reliability score for a single deployment over the last 30 days.
     */
    public function auditDeployment(AgentDeployment $deployment, int $days = 30): array
    {
        $cacheKey = "reliability_audit:{$deployment->id}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deployment, $days) {
            return $this->computeDeploymentScore($deployment, $days);
        });
    }

    /**
     * Aggregate reliability score across all active deployments in an organization.
     */
    public function auditOrganization(Organization $organization, int $days = 30): array
    {
        $deployments = AgentDeployment::withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['active', 'paused'])
            ->get();

        if ($deployments->isEmpty()) {
            return $this->emptyOrgResult($organization->id);
        }

        $scores = $deployments->map(fn ($d) => $this->auditDeployment($d, $days));

        $avgScore = round($scores->avg('score'), 2);
        $below90 = $scores->filter(fn ($s) => $s['score'] < 90)->count();
        $critical = $scores->filter(fn ($s) => $s['score'] < 70)->count();
        $fallingCount = $deployments->filter(fn ($d) => $this->reputationService->isDrifting($d))->count();

        $result = [
            'score' => $avgScore,
            'gate_pass' => $avgScore >= 90,
            'target' => 95,
            'organization_id' => $organization->id,
            'total_deployments' => $deployments->count(),
            'below_90' => $below90,
            'critical' => $critical,
            'drifting' => $fallingCount,
            'deployment_scores' => $scores->toArray(),
            'computed_at' => now()->toIso8601String(),
        ];

        if ($avgScore < 90) {
            Log::warning('[AgentReliabilityAuditor] Org-level reliability below gate', [
                'organization_id' => $organization->id,
                'score' => $avgScore,
                'critical_count' => $critical,
            ]);
        }

        return $result;
    }

    /**
     * Invalidate cached audit for a deployment (call after new scorecard period).
     */
    public function invalidate(AgentDeployment $deployment, int $days = 30): void
    {
        Cache::forget("reliability_audit:{$deployment->id}:{$days}");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Core computation
    // ──────────────────────────────────────────────────────────────────────────

    private function computeDeploymentScore(AgentDeployment $deployment, int $days): array
    {
        $since = now()->subDays($days);

        $tasks = AgentTask::withoutGlobalScope('organization')
            ->where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', $since)
            ->get();

        $decisions = DecisionLog::withoutGlobalScope('organization')
            ->where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', $since)
            ->get();

        $accuracy = $this->scoreAccuracy($decisions);
        $consistency = $this->scoreConsistency($tasks, $decisions);
        $completion = $this->scoreCompletionRate($tasks);
        $success = $this->scoreSuccessRate($tasks, $decisions);
        $falsePos = $this->scoreFalsePositiveRate($decisions);
        $falseNeg = $this->scoreFalseNegativeRate($decisions);

        $total = round(
            $accuracy['score'] + $consistency['score'] + $completion['score']
            + $success['score'] + $falsePos['score'] + $falseNeg['score'],
            2
        );

        return [
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->name,
            'score' => $total,
            'gate_pass' => $total >= 90,
            'days' => $days,
            'dimensions' => compact('accuracy', 'consistency', 'completion', 'success', 'falsePos', 'falseNeg'),
            'task_count' => $tasks->count(),
            'decision_count' => $decisions->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dimension scorers
    // ──────────────────────────────────────────────────────────────────────────

    /** Accuracy (20 pts): decisions with delusion_risk_score < 60 */
    private function scoreAccuracy(mixed $decisions): array
    {
        $total = $decisions->count();
        $highRisk = $decisions->filter(fn ($d) => ($d->delusion_risk_score ?? 0) >= 60)->count();
        $rate = $total > 0 ? 1 - ($highRisk / $total) : 1;
        $score = round($rate * 20, 2);

        return ['score' => $score, 'max' => 20, 'high_risk_decisions' => $highRisk, 'accuracy_rate' => round($rate * 100, 2)];
    }

    /** Consistency (20 pts): low variance in confidence scores (< 15 points std dev) */
    private function scoreConsistency(mixed $tasks, mixed $decisions): array
    {
        $confidences = $tasks->pluck('confidence_score')
            ->merge($decisions->pluck('confidence_score'))
            ->filter()
            ->values();

        if ($confidences->count() < 3) {
            return ['score' => 20, 'max' => 20, 'std_dev' => 0, 'note' => 'insufficient_data'];
        }

        $mean = $confidences->avg();
        $stdDev = sqrt($confidences->map(fn ($v) => ($v - $mean) ** 2)->avg());

        // < 10 std dev = perfect, > 30 = zero score
        $score = round(max(0, (1 - max(0, ($stdDev - 10) / 20)) * 20), 2);

        return ['score' => $score, 'max' => 20, 'std_dev' => round($stdDev, 2), 'mean_confidence' => round($mean, 2)];
    }

    /** Completion Rate (20 pts): completed / (completed + failed) */
    private function scoreCompletionRate(mixed $tasks): array
    {
        $completed = $tasks->whereIn('status', ['completed'])->count();
        $failed = $tasks->whereIn('status', ['failed'])->count();
        $total = $completed + $failed;
        $rate = $total > 0 ? $completed / $total : 1;
        $score = round($rate * 20, 2);

        return ['score' => $score, 'max' => 20, 'completed' => $completed, 'failed' => $failed, 'completion_rate' => round($rate * 100, 2)];
    }

    /** Success Rate (15 pts): decisions that were approved by humans / reviewed decisions */
    private function scoreSuccessRate(mixed $tasks, mixed $decisions): array
    {
        $reviewed = $decisions->where('human_reviewed', true)->count();
        $approved = $decisions->where('human_verdict', 'approved')->count();
        $rate = $reviewed > 0 ? $approved / $reviewed : 1;
        $score = round($rate * 15, 2);

        return ['score' => $score, 'max' => 15, 'reviewed' => $reviewed, 'approved' => $approved, 'success_rate' => round($rate * 100, 2)];
    }

    /** False Positive Rate (15 pts): flagged hallucinations that were incorrect */
    private function scoreFalsePositiveRate(mixed $decisions): array
    {
        $flagged = $decisions->where('delusion_risk_score', '>=', 60)->count();
        $total = $decisions->count();
        $falsePositiveRate = $total > 0 ? $flagged / $total : 0;

        // Low false positive rate = high score
        $score = round(max(0, (1 - $falsePositiveRate) * 15), 2);

        return ['score' => $score, 'max' => 15, 'flagged' => $flagged, 'false_positive_rate' => round($falsePositiveRate * 100, 2)];
    }

    /** False Negative Rate (10 pts): decisions rejected by humans that weren't pre-flagged */
    private function scoreFalseNegativeRate(mixed $decisions): array
    {
        $rejected = $decisions->where('human_verdict', 'rejected')->count();
        $rejectedUnflagged = $decisions
            ->where('human_verdict', 'rejected')
            ->filter(fn ($d) => ($d->delusion_risk_score ?? 0) < 60)
            ->count();

        $total = max(1, $decisions->where('human_reviewed', true)->count());
        $fnRate = $rejectedUnflagged / $total;
        $score = round(max(0, (1 - $fnRate) * 10), 2);

        return ['score' => $score, 'max' => 10, 'rejected_unflagged' => $rejectedUnflagged, 'fn_rate' => round($fnRate * 100, 2)];
    }

    private function emptyOrgResult(int $orgId): array
    {
        return [
            'score' => 100,
            'gate_pass' => true,
            'target' => 95,
            'organization_id' => $orgId,
            'total_deployments' => 0,
            'note' => 'No active deployments to audit.',
        ];
    }
}

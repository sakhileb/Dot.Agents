<?php

namespace App\Services\AI;

/**
 * EnterpriseBrainScorer
 *
 * Pure-computation scorer for the Enterprise Brain Service.
 * All methods are deterministic with no I/O, no Cache, and no side-effects.
 * Extracted from EnterpriseBrainService to enforce SRP and enable unit testing.
 *
 * Responsibilities:
 *  - Translate raw metrics (ROI %, approval counts, bottleneck counts) into scores
 *  - Classify scores into human-readable status labels
 *  - Build the composite domain-score array for EnterpriseHealthScore
 */
class EnterpriseBrainScorer
{
    // ── Core 2: Economic scoring ──────────────────────────────────────────────

    /**
     * Translate an ROI percentage into an economic health score (0–100).
     */
    public function computeEconomicScore(float $roi): int
    {
        return match (true) {
            $roi > 300 => 95,
            $roi > 200 => 85,
            $roi > 100 => 75,
            $roi > 50 => 65,
            $roi > 0 => 55,
            default => 40,
        };
    }

    /**
     * Classify an economic health score into a status label.
     */
    public function computeEconomicStatus(int $score): string
    {
        return match (true) {
            $score >= 75 => 'healthy',
            $score >= 55 => 'monitor',
            default => 'critical',
        };
    }

    // ── Core 4: Governance scoring ────────────────────────────────────────────

    /**
     * Compute a governance health score (0–100) from approval queue backlogs.
     *
     * @param  int  $pendingApprovals  Total pending approval requests
     * @param  int  $overdueApprovals  Approvals pending for > 24 hours
     */
    public function computeGovernanceScore(int $pendingApprovals, int $overdueApprovals): int
    {
        $score = 100 - ($pendingApprovals * 2) - ($overdueApprovals * 5);

        return max(0, min(100, $score));
    }

    /**
     * Classify a governance health score into a compliance status label.
     */
    public function computeGovernanceStatus(int $score): string
    {
        return match (true) {
            $score >= 80 => 'compliant',
            $score >= 60 => 'review_needed',
            default => 'non_compliant',
        };
    }

    // ── Enterprise Health: domain score builder ───────────────────────────────

    /**
     * Build the 8-domain score array used by EnterpriseHealthScore.
     *
     * @param  int  $economicScore  From computeEconomicScore()
     * @param  float  $agentHealthPct  Ratio of non-declining active agents (0–100)
     * @param  int  $bottlenecksDetected  Count of bottlenecked deployments
     * @param  int  $governanceScore  From computeGovernanceScore()
     * @return array<string, float>
     */
    public function buildDomainScores(
        int $economicScore,
        float $agentHealthPct,
        int $bottlenecksDetected,
        int $governanceScore
    ): array {
        $workflowHealth = max(0, 100 - ($bottlenecksDetected * 5));

        return [
            'revenue_health' => (float) $economicScore,
            'customer_health' => 75.0,  // SCCS conversion metrics (placeholder)
            'security_health' => (float) $governanceScore,
            'agent_health' => round($agentHealthPct, 2),
            'workflow_health' => round($workflowHealth, 2),
            'compliance_health' => (float) $governanceScore,
            'operational_health' => round($workflowHealth, 2),
            'technology_health' => 80.0,  // uptime / latency SLAs (placeholder)
        ];
    }

    /**
     * Compute the composite enterprise health score from domain scores.
     *
     * @param  array<string, float>  $domainScores  From buildDomainScores()
     */
    public function computeCompositeScore(array $domainScores): float
    {
        if (empty($domainScores)) {
            return 0.0;
        }

        return round(array_sum($domainScores) / count($domainScores), 2);
    }
}

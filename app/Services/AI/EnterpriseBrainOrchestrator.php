<?php

namespace App\Services\AI;

use App\Models\EnterpriseHealthScore;

/**
 * EnterpriseBrainOrchestrator — composite orchestration layer for the Enterprise Brain.
 *
 * Extracted from EnterpriseBrainService to satisfy SRP:
 *   EnterpriseBrainService   — 5 pure intelligence-core evaluation methods (no I/O orchestration)
 *   EnterpriseBrainOrchestrator — this class — coordinates the 5 cores to produce composite outputs
 *
 * Responsibilities:
 *   - Orchestrate multi-core enterprise health computation → EnterpriseHealthScore
 *   - Generate 30-day risk and opportunity predictions from cross-core signals
 *
 * Not responsible for:
 *   - Individual core intelligence evaluation (stays in EnterpriseBrainService)
 *   - Scoring math (stays in EnterpriseBrainScorer)
 */
class EnterpriseBrainOrchestrator
{
    public function __construct(
        private readonly EnterpriseBrainService $brain,
        private readonly EnterpriseBrainScorer $scorer,
    ) {}

    /**
     * Compute the composite Enterprise Health Score across all intelligence cores.
     *
     * Queries 4 cores (economic, operational, governance, learning), builds domain
     * scores via EnterpriseBrainScorer, and persists a daily snapshot.
     */
    public function computeEnterpriseHealth(int $organizationId): EnterpriseHealthScore
    {
        $governance = $this->brain->assessGovernanceHealth($organizationId);
        $economic = $this->brain->assessEconomicHealth($organizationId);
        $operational = $this->brain->detectBottlenecks($organizationId);
        $learning = $this->brain->generateLearningInsights($organizationId);

        $agentHealth = $learning['total_active_agents'] > 0
            ? max(0, 100 - ($learning['declining_agents'] / $learning['total_active_agents']) * 100)
            : 50.0;

        $domainScores = $this->scorer->buildDomainScores(
            economicScore: $economic['health_score'],
            agentHealthPct: $agentHealth,
            bottlenecksDetected: $operational['bottlenecks_detected'],
            governanceScore: $governance['health_score'],
        );

        $composite = $this->scorer->computeCompositeScore($domainScores);

        return EnterpriseHealthScore::updateOrCreate(
            ['organization_id' => $organizationId, 'scored_at' => today()],
            array_merge($domainScores, [
                'enterprise_health_score' => $composite,
                'domain_details' => [
                    'governance' => $governance,
                    'economic' => $economic,
                    'operational' => $operational,
                    'learning' => $learning,
                ],
                'recommendations' => $this->generatePredictions($organizationId)['risk_factors'],
            ])
        );
    }

    /**
     * Forecast risks and opportunities over the next 30 days based on current core signals.
     *
     * Draws on operational, learning, and governance cores to surface risk factors and
     * autonomy-expansion opportunities.
     */
    public function generatePredictions(int $organizationId): array
    {
        $bottlenecks = $this->brain->detectBottlenecks($organizationId);
        $learning = $this->brain->generateLearningInsights($organizationId);
        $governance = $this->brain->assessGovernanceHealth($organizationId);

        $riskFactors = [];
        $opportunities = [];

        if ($bottlenecks['bottlenecks_detected'] > 3) {
            $riskFactors[] = [
                'type' => 'operational_degradation',
                'probability' => 'high',
                'description' => "{$bottlenecks['bottlenecks_detected']} agent bottlenecks likely to escalate",
                'recommended_action' => 'Review capacity and skill configurations for bottlenecked agents',
            ];
        }

        if ($learning['declining_agents'] > 0) {
            $riskFactors[] = [
                'type' => 'agent_performance_decay',
                'probability' => 'medium',
                'description' => "{$learning['declining_agents']} agents showing performance decline",
                'recommended_action' => 'Schedule DWCA re-certification for declining agents',
            ];
        }

        if ($governance['overdue_approvals'] > 5) {
            $riskFactors[] = [
                'type' => 'approval_backlog',
                'probability' => 'high',
                'description' => 'Approval queue backlog may create operational bottleneck',
                'recommended_action' => 'Adjust confidence thresholds or add human reviewers',
            ];
        }

        if ($learning['improving_agents'] > $learning['declining_agents']) {
            $opportunities[] = [
                'type' => 'autonomy_expansion',
                'description' => 'Strong performance trends support increasing agent autonomy levels',
                'estimated_efficiency_gain' => '15-25%',
            ];
        }

        return [
            'core' => 'predictive',
            'forecast_horizon_days' => 30,
            'risk_factors' => $riskFactors,
            'opportunities' => $opportunities,
            'prediction_confidence' => 72,
        ];
    }
}

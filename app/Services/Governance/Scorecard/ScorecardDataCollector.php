<?php

namespace App\Services\Governance\Scorecard;

use App\Models\Organization;
use App\Services\Governance\AgentReliabilityAuditorService;
use App\Services\Governance\CustomerSuccessService;
use App\Services\Governance\DataTrustScoreService;
use App\Services\Governance\DigitalImmuneSystem;
use App\Services\Governance\FinancialIntelligenceService;
use App\Services\Governance\OrganizationalMemoryService;
use App\Services\Governance\PredictionAccuracyTrackingService;
use App\Services\Infrastructure\ObservabilityService;

/**
 * ScorecardDataCollector — single injectable that gathers all raw domain
 * scores needed by MegaV2ScorecardService. Keeps the orchestrator lean and
 * lets each data source be swapped or mocked independently.
 */
class ScorecardDataCollector
{
    public function __construct(
        private readonly DataTrustScoreService $dataTrust,
        private readonly AgentReliabilityAuditorService $reliabilityAuditor,
        private readonly PredictionAccuracyTrackingService $predictionAccuracy,
        private readonly OrganizationalMemoryService $orgMemory,
        private readonly ObservabilityService $observability,
        private readonly DigitalImmuneSystem $dis,
        private readonly FinancialIntelligenceService $financial,
        private readonly CustomerSuccessService $customerSuccess,
    ) {}

    /**
     * Collect all source scores for the given organization.
     *
     * @return array{
     *   dataTrust: array,
     *   agentReliability: array,
     *   predictionAcc: array,
     *   orgMemoryScore: array,
     *   observability: array,
     *   disResult: array,
     *   financialScore: array,
     *   csScore: array,
     * }
     */
    public function collect(Organization $organization): array
    {
        return [
            'dataTrust' => $this->dataTrust->calculate($organization),
            'agentReliability' => $this->reliabilityAuditor->auditOrganization($organization),
            'predictionAcc' => $this->predictionAccuracy->calculateForOrganization($organization),
            'orgMemoryScore' => $this->orgMemory->calculate($organization),
            'observability' => $this->observability->observabilityScore(),
            'disResult' => $this->dis->runHealthCheck($organization->id),
            'financialScore' => $this->financial->calculate($organization),
            'csScore' => $this->customerSuccess->calculate($organization),
        ];
    }
}

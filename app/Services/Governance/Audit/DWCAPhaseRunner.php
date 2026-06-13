<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;

/**
 * DWCAPhaseRunner — groups all DWCA phase strategy classes into a single
 * injectable dependency. This keeps DWCAAuditService lean while preserving
 * each phase's independent testability.
 */
class DWCAPhaseRunner
{
    public function __construct(
        private readonly Phase01AgentDiscovery $phase01,
        private readonly Phase02SkillAudit $phase02,
        private readonly Phase04AgentQuality $phase04,
        private readonly Phase06Governance $phase06,
        private readonly Phase07DelusionRisk $phase07,
        private readonly Phase08Memory $phase08,
        private readonly Phase12Performance $phase12,
        private readonly Phase13Scorecard $phase13,
    ) {}

    /**
     * Execute all phases for a deployment and return keyed phase results.
     *
     * @return array{
     *   phase1_discovery: array,
     *   phase2_skill_audit: array,
     *   phase4_quality: array,
     *   phase6_governance: array,
     *   phase7_delusion: array,
     *   phase8_memory: array,
     *   phase12_performance: array,
     *   phase13_scorecard: array,
     * }
     */
    public function run(AgentDeployment $deployment): array
    {
        return [
            'phase1_discovery' => $this->phase01->execute($deployment),
            'phase2_skill_audit' => $this->phase02->execute($deployment),
            'phase4_quality' => $this->phase04->execute($deployment),
            'phase6_governance' => $this->phase06->execute($deployment),
            'phase7_delusion' => $this->phase07->execute($deployment),
            'phase8_memory' => $this->phase08->execute($deployment),
            'phase12_performance' => $this->phase12->execute($deployment),
            'phase13_scorecard' => $this->phase13->execute($deployment),
        ];
    }

    /** Return phase results as a flat ordered list (for composite score computation). */
    public function scores(array $phases): array
    {
        return [
            $phases['phase1_discovery']['score'],
            $phases['phase2_skill_audit']['score'],
            $phases['phase4_quality']['score'],
            $phases['phase6_governance']['score'],
            $phases['phase7_delusion']['score'],
            $phases['phase8_memory']['score'],
            $phases['phase12_performance']['score'],
            $phases['phase13_scorecard']['score'],
        ];
    }
}

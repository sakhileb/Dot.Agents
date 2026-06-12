<?php

namespace App\Services\Skills;

use App\Models\AgentSkillApproval;
use App\Models\AgentSkillScore;

/**
 * SkillQueryService — read-only query layer for skill-related data.
 *
 * Centralises all Eloquent reads for skills so API controllers remain
 * thin and query logic stays testable in isolation.
 */
class SkillQueryService
{
    /**
     * Fetch paginated skill approvals for an organisation, with optional
     * status and risk-level filters.
     */
    public function getPendingApprovals(
        int $organizationId,
        ?string $status = null,
        ?string $riskLevel = null,
        int $perPage = 20,
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        return AgentSkillApproval::where('organization_id', $organizationId)
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when($riskLevel !== null, fn ($q) => $q->forRisk($riskLevel))
            ->with(['skill:id,name,department,risk_level', 'requestedBy:id,name', 'deployment:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Fetch skill scores for a deployment within a billing period.
     */
    public function getDeploymentScores(
        int $deploymentId,
        int $organizationId,
        string $period,
    ): \Illuminate\Database\Eloquent\Collection {
        return AgentSkillScore::where('agent_deployment_id', $deploymentId)
            ->where('organization_id', $organizationId)
            ->where('period', $period)
            ->with('skill:id,name,key,department,risk_level')
            ->orderByDesc('success_rate')
            ->get();
    }
}

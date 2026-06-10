<?php

namespace App\Services\Governance\Financial;

use App\Models\AgentScorecard;
use Illuminate\Support\Collection;

/**
 * FinancialTrendAnalyzer
 *
 * Computes monthly cost/savings trends for Financial Intelligence scoring.
 *
 * Extracted from FinancialIntelligenceService.
 */
class FinancialTrendAnalyzer
{
    private const LOOKBACK_MONTHS = 3;

    /**
     * Build a monthly cost/savings trend array for the given deployment IDs.
     * Groups AgentScorecard records by month over the last N months.
     *
     * @param  Collection  $deploymentIds
     * @return array<array{month: string, cost: float, savings: float, records: int}>
     */
    public function compute(Collection $deploymentIds): array
    {
        if ($deploymentIds->isEmpty()) {
            return [];
        }

        $rows = AgentScorecard::withoutGlobalScope('organization')
            ->whereHas('agentDeployment', fn ($q) => $q->whereIn('id', $deploymentIds))
            ->where('period_end', '>=', now()->subMonths(self::LOOKBACK_MONTHS))
            ->selectRaw("strftime('%Y-%m', period_end) as month, SUM(total_cost) as cost, SUM(estimated_savings) as savings, COUNT(*) as records")
            ->groupByRaw("strftime('%Y-%m', period_end)")
            ->orderByRaw("strftime('%Y-%m', period_end)")
            ->get();

        return $rows->map(fn ($r) => [
            'month' => $r->month,
            'cost' => (float) $r->cost,
            'savings' => (float) $r->savings,
            'records' => (int) $r->records,
        ])->toArray();
    }
}

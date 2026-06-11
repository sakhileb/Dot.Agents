<?php

namespace App\Services\Governance\Financial;

use App\Models\AgentScorecard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * @return array<array{month: string, cost: float, savings: float, records: int}>
     */
    public function compute(Collection $deploymentIds): array
    {
        if ($deploymentIds->isEmpty()) {
            return [];
        }

        // Use DB-driver-aware date formatting: MySQL uses DATE_FORMAT, SQLite uses strftime.
        $dateExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', period_end)"
            : "DATE_FORMAT(period_end, '%Y-%m')";

        $rows = AgentScorecard::withoutGlobalScope('organization')
            ->whereHas('agentDeployment', fn ($q) => $q->whereIn('id', $deploymentIds))
            ->where('period_end', '>=', now()->subMonths(self::LOOKBACK_MONTHS))
            ->selectRaw("{$dateExpr} as month, SUM(total_cost) as cost, SUM(estimated_savings) as savings, COUNT(*) as records")
            ->groupByRaw($dateExpr)
            ->orderByRaw($dateExpr)
            ->get();

        return $rows->map(fn ($r) => [
            'month' => $r->month,
            'cost' => (float) $r->cost,
            'savings' => (float) $r->savings,
            'records' => (int) $r->records,
        ])->toArray();
    }
}

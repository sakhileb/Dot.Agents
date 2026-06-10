<?php

namespace App\Services\AI;

use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\UsageRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AgentQuotaGuard
 *
 * Enforces the monthly task quota from the organization's subscription plan.
 *
 * Uses a cached counter for performance, invalidated at month rollover.
 * Raises a RuntimeException when the quota has been exhausted.
 *
 * Extracted from AgentOrchestrationService to keep orchestration focused.
 */
class AgentQuotaGuard
{
    /**
     * Assert that the organization has remaining task quota for this month.
     *
     * @param  string|null  $planSlug  The org's current subscription plan slug
     *
     * @throws \RuntimeException when the monthly task quota is exhausted
     */
    public function assertQuotaAvailable(int $organizationId, ?string $planSlug): void
    {
        $plan = $planSlug
            ? Cache::remember("plan:{$planSlug}", 3600, fn () => SubscriptionPlan::where('slug', $planSlug)->first())
            : null;

        $limit = $plan?->max_tasks_per_month ?? PHP_INT_MAX;

        if ($limit === PHP_INT_MAX) {
            return; // Unlimited — skip count query
        }

        $cacheKey = "org_task_quota:{$organizationId}:".now()->format('Y-m');

        $used = Cache::remember($cacheKey, 300, fn () => UsageRecord::withoutGlobalScope('organization')
            ->where('organization_id', $organizationId)
            ->where('record_type', 'task')
            ->whereYear('recorded_at', now()->year)
            ->whereMonth('recorded_at', now()->month)
            ->count()
        );

        if ($used >= $limit) {
            Log::warning('[AgentQuotaGuard] Monthly task quota exceeded', [
                'organization_id' => $organizationId,
                'used' => $used,
                'limit' => $limit,
            ]);

            throw new \RuntimeException(
                "Monthly task quota of {$limit} tasks has been reached for organization [{$organizationId}]. "
                .'Upgrade your plan or wait until next month.'
            );
        }

        // Invalidate count cache so the next check reflects this task
        Cache::forget($cacheKey);
    }
}

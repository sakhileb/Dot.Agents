<?php

namespace App\Actions\Billing;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Gate;

class ActivateSubscriptionAction
{
    public function execute(Organization $organization, SubscriptionPlan $plan, string $billingPeriod = 'monthly'): OrganizationSubscription
    {
        Gate::authorize('manage-billing', $organization);

        // Deactivate any existing active subscription
        OrganizationSubscription::where('organization_id', $organization->id)
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $subscription = OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $billingPeriod,
            'amount' => $billingPeriod === 'annual' ? ($plan->yearly_price ?? $plan->price ?? 0) : ($plan->price ?? 0),
            'currency' => 'USD',
            'current_period_start' => now(),
            'current_period_end' => $billingPeriod === 'annual' ? now()->addYear() : now()->addMonth(),
        ]);

        $organization->update(['plan' => $plan->slug ?? strtolower($plan->name)]);

        return $subscription;
    }
}

<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationSubscriptionFactory extends Factory
{
    protected $model = OrganizationSubscription::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 99.00,
            'currency' => 'USD',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancelled_at' => null,
            'metadata' => [],
        ];
    }

    public function annual(): static
    {
        return $this->state(fn () => [
            'billing_cycle' => 'annual',
            'amount' => 990.00,
            'current_period_end' => now()->addYear(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}

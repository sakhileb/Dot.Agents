<?php

namespace Tests\Feature\Actions\Billing;

use App\Actions\Billing\ActivateSubscriptionAction;
use App\DTOs\Billing\ActivateSubscriptionData;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ActivateSubscriptionActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Gate::before(fn () => true);
    }

    public function test_creates_active_subscription(): void
    {
        $this->actingAs($this->user);
        $plan = SubscriptionPlan::factory()->create([
            'price' => 99.00,
            'yearly_price' => 990.00,
        ]);

        $subscription = app(ActivateSubscriptionAction::class)
            ->execute(new ActivateSubscriptionData($this->organization->id, $plan->id, 'monthly'));

        $this->assertInstanceOf(OrganizationSubscription::class, $subscription);
        $this->assertSame('active', $subscription->status);
        $this->assertSame($this->organization->id, $subscription->organization_id);
        $this->assertSame($plan->id, $subscription->plan_id);
    }

    public function test_cancels_existing_subscription_before_activating(): void
    {
        $this->actingAs($this->user);
        $plan = SubscriptionPlan::factory()->create();

        // Create an existing active subscription
        OrganizationSubscription::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'active',
        ]);

        app(ActivateSubscriptionAction::class)->execute(new ActivateSubscriptionData($this->organization->id, $plan->id, 'monthly'));

        // The old subscription should be cancelled
        $this->assertDatabaseHas('organization_subscriptions', [
            'organization_id' => $this->organization->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_sets_annual_period_end_correctly(): void
    {
        $this->actingAs($this->user);
        $plan = SubscriptionPlan::factory()->create();

        $subscription = app(ActivateSubscriptionAction::class)
            ->execute(new ActivateSubscriptionData($this->organization->id, $plan->id, 'annual'));

        $this->assertSame('annual', $subscription->billing_cycle);
        $this->assertTrue($subscription->current_period_end->isAfter(now()->addMonths(11)));
    }

    public function test_updates_organization_plan(): void
    {
        $this->actingAs($this->user);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Professional']);

        app(ActivateSubscriptionAction::class)->execute(new ActivateSubscriptionData($this->organization->id, $plan->id));

        $this->organization->refresh();
        $this->assertNotNull($this->organization->plan);
    }
}

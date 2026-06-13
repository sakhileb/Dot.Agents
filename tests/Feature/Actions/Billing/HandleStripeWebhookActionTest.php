<?php

namespace Tests\Feature\Actions\Billing;

use App\Actions\Billing\HandleStripeWebhookAction;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class HandleStripeWebhookActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->plan = SubscriptionPlan::factory()->create([
            'price' => 99.00,
            'billing_cycle' => 'monthly',
        ]);
        Gate::before(fn () => true);
    }

    public function test_ignores_duplicate_stripe_events(): void
    {
        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldNotReceive('getSubscription');
        $this->app->instance(StripeService::class, $stripe);

        $event = $this->makeStripeEvent('checkout.session.completed', new \stdClass);
        cache()->put('stripe_event_'.$event->id, true, 172800);

        app(HandleStripeWebhookAction::class)->execute($event);

        $this->assertDatabaseMissing('organization_subscriptions', [
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_checkout_session_completed_creates_subscription(): void
    {
        $stripeSubscription = new \stdClass;
        $stripeSubscription->id = 'sub_test_123';
        $stripeSubscription->current_period_start = now()->timestamp;
        $stripeSubscription->current_period_end = now()->addMonth()->timestamp;

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('getSubscription')->once()->andReturn($stripeSubscription);
        $this->app->instance(StripeService::class, $stripe);

        $session = new \stdClass;
        $session->id = 'cs_test_'.str()->random(10);
        $session->subscription = 'sub_test_123';
        $session->metadata = (object) [
            'organization_id' => $this->organization->id,
            'subscription_plan_id' => $this->plan->id,
        ];

        $event = $this->makeStripeEvent('checkout.session.completed', $session);

        app(HandleStripeWebhookAction::class)->execute($event);

        $this->assertDatabaseHas('organization_subscriptions', [
            'organization_id' => $this->organization->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'stripe_subscription_id' => 'sub_test_123',
        ]);
    }

    public function test_subscription_deleted_cancels_subscription(): void
    {
        $stripe = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $stripe);

        OrganizationSubscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'stripe_subscription_id' => 'sub_to_cancel',
            'amount' => 99.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $stripeSubscription = new \stdClass;
        $stripeSubscription->id = 'sub_to_cancel';
        $stripeSubscription->status = 'canceled';
        $stripeSubscription->metadata = (object) ['organization_id' => $this->organization->id];
        $stripeSubscription->current_period_start = now()->timestamp;
        $stripeSubscription->current_period_end = now()->addMonth()->timestamp;

        $event = $this->makeStripeEvent('customer.subscription.deleted', $stripeSubscription);

        app(HandleStripeWebhookAction::class)->execute($event);

        $this->assertDatabaseHas('organization_subscriptions', [
            'organization_id' => $this->organization->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_subscription_updated_changes_period(): void
    {
        $stripe = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $stripe);

        OrganizationSubscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'stripe_subscription_id' => 'sub_to_update',
            'amount' => 99.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now(),
        ]);

        $newPeriodEnd = now()->addMonth();

        $stripeSubscription = new \stdClass;
        $stripeSubscription->id = 'sub_to_update';
        $stripeSubscription->status = 'active';
        $stripeSubscription->metadata = (object) ['organization_id' => $this->organization->id];
        $stripeSubscription->current_period_start = now()->timestamp;
        $stripeSubscription->current_period_end = $newPeriodEnd->timestamp;

        $event = $this->makeStripeEvent('customer.subscription.updated', $stripeSubscription);

        app(HandleStripeWebhookAction::class)->execute($event);

        $sub = OrganizationSubscription::where('organization_id', $this->organization->id)->first();
        $this->assertEqualsWithDelta($newPeriodEnd->timestamp, $sub->current_period_end->timestamp, 5);
    }

    public function test_unhandled_event_type_does_not_throw(): void
    {
        $stripe = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $stripe);

        $event = $this->makeStripeEvent('payment_method.attached', new \stdClass);

        app(HandleStripeWebhookAction::class)->execute($event);
        $this->assertTrue(true);
    }

    private function makeStripeEvent(string $type, object $dataObject): object
    {
        return (object) [
            'id' => 'evt_'.str()->random(20),
            'type' => $type,
            'data' => (object) ['object' => $dataObject],
        ];
    }
}

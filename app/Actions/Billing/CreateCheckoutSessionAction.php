<?php

namespace App\Actions\Billing;

use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Services\Billing\StripeService;
use Illuminate\Support\Facades\Gate;
use Stripe\Checkout\Session;

class CreateCheckoutSessionAction
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    /** @return Session */
    public function execute(
        Organization $organization,
        SubscriptionPlan $plan,
        string $successUrl,
        string $cancelUrl,
    ): object {
        Gate::authorize('manage-billing', $organization);

        return $this->stripe->createCheckoutSession(
            $organization,
            $plan,
            $successUrl,
            $cancelUrl,
        );
    }
}

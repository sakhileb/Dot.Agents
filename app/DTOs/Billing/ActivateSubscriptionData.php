<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

readonly class ActivateSubscriptionData
{
    public function __construct(
        public int $organizationId,
        public int $planId,
        public string $billingPeriod = 'monthly',
        public ?string $stripeSubscriptionId = null,
        public ?string $stripeCustomerId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            planId: (int) $data['plan_id'],
            billingPeriod: $data['billing_period'] ?? 'monthly',
            stripeSubscriptionId: $data['stripe_subscription_id'] ?? null,
            stripeCustomerId: $data['stripe_customer_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'plan_id' => $this->planId,
            'billing_period' => $this->billingPeriod,
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'stripe_customer_id' => $this->stripeCustomerId,
        ];
    }
}

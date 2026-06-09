<?php

namespace App\DTOs\Billing;

readonly class CreateCheckoutSessionData
{
    public function __construct(
        public int $organizationId,
        public int $planId,
        public string $successUrl,
        public string $cancelUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            planId: (int) $data['plan_id'],
            successUrl: $data['success_url'],
            cancelUrl: $data['cancel_url'],
        );
    }
}

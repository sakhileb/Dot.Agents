<?php

namespace App\DTOs\Organizations;

readonly class UpdateOrganizationSettingsData
{
    /** Allowed top-level fields that may be updated on an Organization. */
    private const ALLOWED = [
        'name', 'domain', 'logo', 'industry', 'size',
        'country', 'timezone', 'currency', 'settings', 'billing_address',
    ];

    public function __construct(
        public ?string $name = null,
        public ?string $domain = null,
        public ?string $logo = null,
        public ?string $industry = null,
        public ?string $size = null,
        public ?string $country = null,
        public ?string $timezone = null,
        public ?string $currency = null,
        public ?array $settings = null,
        public ?array $billingAddress = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Only accept keys in the allowed list to prevent mass-assignment at DTO level
        $data = array_intersect_key($data, array_flip(self::ALLOWED));

        return new self(
            name: $data['name'] ?? null,
            domain: $data['domain'] ?? null,
            logo: $data['logo'] ?? null,
            industry: $data['industry'] ?? null,
            size: $data['size'] ?? null,
            country: $data['country'] ?? null,
            timezone: $data['timezone'] ?? null,
            currency: $data['currency'] ?? null,
            settings: isset($data['settings']) ? (array) $data['settings'] : null,
            billingAddress: isset($data['billing_address']) ? (array) $data['billing_address'] : null,
        );
    }

    /** Return only non-null fields as a plain array for Eloquent update(). */
    public function toArray(): array
    {
        $map = [
            'name' => $this->name,
            'domain' => $this->domain,
            'logo' => $this->logo,
            'industry' => $this->industry,
            'size' => $this->size,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'settings' => $this->settings,
            'billing_address' => $this->billingAddress,
        ];

        return array_filter($map, fn ($v) => $v !== null);
    }
}

<?php

declare(strict_types=1);

namespace App\DTOs\Organizations;

readonly class UpdateOrganizationData
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $website = null,
        public ?string $industry = null,
        public ?string $size = null,
        public ?string $timezone = null,
        public ?array $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            website: $data['website'] ?? null,
            industry: $data['industry'] ?? null,
            size: $data['size'] ?? null,
            timezone: $data['timezone'] ?? null,
            settings: isset($data['settings']) ? (array) $data['settings'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'website' => $this->website,
            'industry' => $this->industry,
            'size' => $this->size,
            'timezone' => $this->timezone,
            'settings' => $this->settings,
        ], fn ($v) => $v !== null);
    }
}

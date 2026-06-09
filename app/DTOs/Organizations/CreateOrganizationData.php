<?php

namespace App\DTOs\Organizations;

readonly class CreateOrganizationData
{
    public function __construct(
        public string $name,
        public string $industry,
        public string $size,
        public ?string $domain = null,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            industry: $data['industry'],
            size: $data['size'],
            domain: $data['domain'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}

<?php

namespace App\DTOs\Organizations;

readonly class SaveKnowledgeBaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public string $type = 'general',
        public string $accessLevel = 'internal',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            type: $data['type'] ?? 'general',
            accessLevel: $data['access_level'] ?? 'internal',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'access_level' => $this->accessLevel,
        ];
    }
}

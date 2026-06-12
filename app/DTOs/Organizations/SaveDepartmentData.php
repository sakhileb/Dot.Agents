<?php

namespace App\DTOs\Organizations;

readonly class SaveDepartmentData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $headName = null,
        public ?int $existingId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            type: $data['type'] ?? null,
            headName: $data['head_name'] ?? null,
            existingId: isset($data['existing_id']) ? (int) $data['existing_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'head_name' => $this->headName,
        ];
    }
}

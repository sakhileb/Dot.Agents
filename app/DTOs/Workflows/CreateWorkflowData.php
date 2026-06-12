<?php

namespace App\DTOs\Workflows;

readonly class CreateWorkflowData
{
    public function __construct(
        public string $name,
        public string $triggerType,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            triggerType: $data['trigger_type'],
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'trigger_type' => $this->triggerType,
            'description' => $this->description,
        ];
    }
}

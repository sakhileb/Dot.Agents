<?php

declare(strict_types=1);

namespace App\DTOs\Organizations;

readonly class SaveConnectionSettingsData
{
    public function __construct(
        public array $goals = [],
        public array $aiFeatures = [],
        public array $permissions = [],
        public int $autonomyLevel = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goals: $data['goals'] ?? [],
            aiFeatures: $data['ai_features'] ?? [],
            permissions: $data['permissions'] ?? [],
            autonomyLevel: (int) ($data['autonomy_level'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'goals' => $this->goals,
            'ai_features' => $this->aiFeatures,
            'permissions' => $this->permissions,
            'autonomy_level' => $this->autonomyLevel,
        ];
    }
}

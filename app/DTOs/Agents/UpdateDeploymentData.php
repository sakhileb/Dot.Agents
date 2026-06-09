<?php

declare(strict_types=1);

namespace App\DTOs\Agents;

readonly class UpdateDeploymentData
{
    public function __construct(
        public ?string $name = null,
        public ?string $deploymentMode = null,
        public ?float $confidenceThreshold = null,
        public ?string $customInstructions = null,
        public ?int $departmentId = null,
        public ?int $teamId = null,
        public ?bool $isActive = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            deploymentMode: $data['deployment_mode'] ?? null,
            confidenceThreshold: isset($data['confidence_threshold']) ? (float) $data['confidence_threshold'] : null,
            customInstructions: $data['custom_instructions'] ?? null,
            departmentId: isset($data['department_id']) ? (int) $data['department_id'] : null,
            teamId: isset($data['team_id']) ? (int) $data['team_id'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'deployment_mode' => $this->deploymentMode,
            'confidence_threshold' => $this->confidenceThreshold,
            'custom_instructions' => $this->customInstructions,
            'department_id' => $this->departmentId,
            'team_id' => $this->teamId,
            'is_active' => $this->isActive,
        ], fn ($v) => $v !== null);
    }
}

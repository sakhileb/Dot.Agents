<?php

namespace App\DTOs\Agents;

readonly class ResumeDeploymentData
{
    public function __construct(
        public int $deploymentId,
        public int $resumedBy,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            deploymentId: (int) $data['deployment_id'],
            resumedBy: (int) $data['resumed_by'],
            notes: $data['notes'] ?? null,
        );
    }
}

<?php

namespace App\DTOs\Agents;

readonly class PauseDeploymentData
{
    public function __construct(
        public int $deploymentId,
        public int $pausedBy,
        public ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            deploymentId: (int) $data['deployment_id'],
            pausedBy: (int) $data['paused_by'],
            reason: $data['reason'] ?? null,
        );
    }
}

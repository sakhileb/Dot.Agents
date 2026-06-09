<?php

declare(strict_types=1);

namespace App\DTOs\Agents;

readonly class DecommissionDeploymentData
{
    public function __construct(
        public string $reason = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reason: (string) ($data['reason'] ?? ''),
        );
    }
}

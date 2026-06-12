<?php

declare(strict_types=1);

namespace App\DTOs\Social;

readonly class MarkEscalationHandledData
{
    public function __construct(
        public int $organizationId,
        public int $scoreId,
    ) {}

    public static function from(int $organizationId, int $scoreId): self
    {
        return new self(organizationId: $organizationId, scoreId: $scoreId);
    }
}

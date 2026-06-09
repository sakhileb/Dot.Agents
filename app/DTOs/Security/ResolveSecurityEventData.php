<?php

namespace App\DTOs\Security;

readonly class ResolveSecurityEventData
{
    public function __construct(
        public int $eventId,
        public int $resolvedBy,
        public ?string $remediationNotes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            eventId: (int) $data['event_id'],
            resolvedBy: (int) $data['resolved_by'],
            remediationNotes: $data['remediation_notes'] ?? null,
        );
    }
}

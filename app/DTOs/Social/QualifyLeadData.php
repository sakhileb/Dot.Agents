<?php

namespace App\DTOs\Social;

readonly class QualifyLeadData
{
    public function __construct(
        public int $leadId,
        public int $actorId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            leadId: (int) $data['lead_id'],
            actorId: (int) $data['actor_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'lead_id' => $this->leadId,
            'actor_id' => $this->actorId,
        ];
    }
}

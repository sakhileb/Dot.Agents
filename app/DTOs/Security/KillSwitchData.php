<?php

namespace App\DTOs\Security;

readonly class KillSwitchData
{
    public function __construct(
        public string $reason,
        public int $triggeredBy,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reason: $data['reason'] ?? 'Emergency kill switch activated',
            triggeredBy: (int) $data['triggered_by'],
        );
    }

    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'triggered_by' => $this->triggeredBy,
        ];
    }
}

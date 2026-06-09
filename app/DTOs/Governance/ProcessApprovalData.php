<?php

namespace App\DTOs\Governance;

readonly class ProcessApprovalData
{
    public function __construct(
        public int $approvalId,
        public string $decision,
        public ?string $reviewerNotes = null,
    ) {
        if (! in_array($decision, ['approved', 'rejected', 'escalated'], true)) {
            throw new \InvalidArgumentException("Invalid decision: {$decision}");
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            approvalId: (int) $data['approval_id'],
            decision: $data['decision'],
            reviewerNotes: $data['reviewer_notes'] ?? null,
        );
    }
}

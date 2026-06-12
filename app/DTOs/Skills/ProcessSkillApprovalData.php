<?php

namespace App\DTOs\Skills;

readonly class ProcessSkillApprovalData
{
    public function __construct(
        public int $approvalId,
        public string $decision,
        public int $reviewerId,
        public ?string $reviewerNotes = null,
    ) {
        if (! in_array($this->decision, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException("Invalid decision: {$this->decision}. Must be 'approved' or 'rejected'.");
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            approvalId: (int) $data['approval_id'],
            decision: $data['decision'],
            reviewerId: (int) $data['reviewer_id'],
            reviewerNotes: $data['reviewer_notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'approval_id' => $this->approvalId,
            'decision' => $this->decision,
            'reviewer_id' => $this->reviewerId,
            'reviewer_notes' => $this->reviewerNotes,
        ];
    }
}

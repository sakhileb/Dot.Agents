<?php

namespace App\DTOs\Social;

readonly class EscalateConversationData
{
    public function __construct(
        public int $conversationId,
        public int $escalatedTo,
        public int $escalatedBy,
        public string $reason = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            conversationId: (int) $data['conversation_id'],
            escalatedTo: (int) $data['escalated_to'],
            escalatedBy: (int) $data['escalated_by'],
            reason: $data['reason'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'escalated_to' => $this->escalatedTo,
            'escalated_by' => $this->escalatedBy,
            'reason' => $this->reason,
        ];
    }
}

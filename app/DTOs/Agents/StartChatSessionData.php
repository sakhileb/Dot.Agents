<?php

namespace App\DTOs\Agents;

readonly class StartChatSessionData
{
    public function __construct(
        public int $deploymentId,
        public int $userId,
        public int $organizationId,
        public string $sessionTitle = 'New Conversation',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            deploymentId: (int) $data['deployment_id'],
            userId: (int) $data['user_id'],
            organizationId: (int) $data['organization_id'],
            sessionTitle: $data['session_title'] ?? 'New Conversation',
        );
    }
}

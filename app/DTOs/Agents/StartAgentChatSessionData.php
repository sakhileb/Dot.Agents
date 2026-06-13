<?php

namespace App\DTOs\Agents;

readonly class StartAgentChatSessionData
{
    public function __construct(
        public int $userId,
        public int $agentDeploymentId,
        public ?int $organizationId = null,
        public ?string $title = null,
        public ?string $initialMessage = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            agentDeploymentId: (int) $data['agent_deployment_id'],
            organizationId: isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            title: $data['title'] ?? null,
            initialMessage: $data['initial_message'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'agent_deployment_id' => $this->agentDeploymentId,
            'organization_id' => $this->organizationId,
            'title' => $this->title,
            'initial_message' => $this->initialMessage,
        ], fn ($v) => $v !== null);
    }
}

<?php

namespace App\DTOs\Social;

readonly class SentimentAnalysisData
{
    public function __construct(
        public int $organizationId,
        public string $subjectType,
        public string $text,
        public string $platform = '',
        public ?int $socialAccountId = null,
        public ?int $socialConversationId = null,
        public ?int $agentDeploymentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            subjectType: $data['subject_type'],
            text: $data['text'],
            platform: $data['platform'] ?? '',
            socialAccountId: isset($data['social_account_id']) ? (int) $data['social_account_id'] : null,
            socialConversationId: isset($data['social_conversation_id']) ? (int) $data['social_conversation_id'] : null,
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
        );
    }
}

<?php

namespace App\DTOs\Social;

readonly class SocialMessageResponseData
{
    public function __construct(
        public int $organizationId,
        public int $socialConversationId,
        public string $content,
        public string $messageType = 'text',
        public array $mediaAttachments = [],
        public ?int $agentDeploymentId = null,
        public bool $isAiGenerated = false,
        public bool $requiresApproval = false,
        public bool $wasDisclosedAsAi = false,
        public float $aiConfidence = 0.0,
        public array $aiContext = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            socialConversationId: (int) $data['social_conversation_id'],
            content: $data['content'],
            messageType: $data['message_type'] ?? 'text',
            mediaAttachments: $data['media_attachments'] ?? [],
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
            isAiGenerated: (bool) ($data['is_ai_generated'] ?? false),
            requiresApproval: (bool) ($data['requires_approval'] ?? false),
            wasDisclosedAsAi: (bool) ($data['was_disclosed_as_ai'] ?? false),
            aiConfidence: (float) ($data['ai_confidence'] ?? 0.0),
            aiContext: $data['ai_context'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'social_conversation_id' => $this->socialConversationId,
            'content' => $this->content,
            'message_type' => $this->messageType,
            'media_attachments' => $this->mediaAttachments,
            'agent_deployment_id' => $this->agentDeploymentId,
            'direction' => 'outbound',
            'sender_type' => $this->isAiGenerated ? 'agent' : 'human_agent',
            'is_ai_generated' => $this->isAiGenerated,
            'requires_approval' => $this->requiresApproval,
            'approval_status' => $this->requiresApproval ? 'pending' : null,
            'was_disclosed_as_ai' => $this->wasDisclosedAsAi,
            'ai_confidence' => $this->aiConfidence,
            'ai_context' => $this->aiContext,
        ];
    }
}

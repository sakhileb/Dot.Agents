<?php

namespace App\DTOs\Social;

readonly class SocialPostData
{
    public function __construct(
        public int $organizationId,
        public int $socialPageId,
        public string $content,
        public string $postType = 'post',
        public array $mediaUrls = [],
        public array $hashtags = [],
        public array $mentions = [],
        public ?string $linkUrl = null,
        public ?int $campaignId = null,
        public ?int $agentDeploymentId = null,
        public ?\DateTimeInterface $scheduledAt = null,
        public bool $requiresApproval = true,
        public array $aiMetadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            socialPageId: (int) $data['social_page_id'],
            content: $data['content'],
            postType: $data['post_type'] ?? 'post',
            mediaUrls: $data['media_urls'] ?? [],
            hashtags: $data['hashtags'] ?? [],
            mentions: $data['mentions'] ?? [],
            linkUrl: $data['link_url'] ?? null,
            campaignId: isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTime($data['scheduled_at']) : null,
            requiresApproval: (bool) ($data['requires_approval'] ?? true),
            aiMetadata: $data['ai_metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'social_page_id' => $this->socialPageId,
            'content' => $this->content,
            'post_type' => $this->postType,
            'media_urls' => $this->mediaUrls,
            'hashtags' => $this->hashtags,
            'mentions' => $this->mentions,
            'link_url' => $this->linkUrl,
            'campaign_id' => $this->campaignId,
            'agent_deployment_id' => $this->agentDeploymentId,
            'scheduled_at' => $this->scheduledAt,
            'ai_metadata' => $this->aiMetadata,
        ];
    }
}

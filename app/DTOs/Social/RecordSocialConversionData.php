<?php

namespace App\DTOs\Social;

readonly class RecordSocialConversionData
{
    public function __construct(
        public int $organizationId,
        public string $conversionType,
        public int $actorId,
        public ?int $socialLeadId = null,
        public ?int $socialConversationId = null,
        public ?int $agentDeploymentId = null,
        public ?int $campaignId = null,
        public ?float $revenue = null,
        public string $currency = 'USD',
        public ?string $productOrService = null,
        public float $agentAttributionScore = 0.0,
        public array $attributionPath = [],
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            conversionType: $data['conversion_type'],
            actorId: (int) $data['actor_id'],
            socialLeadId: isset($data['social_lead_id']) ? (int) $data['social_lead_id'] : null,
            socialConversationId: isset($data['social_conversation_id']) ? (int) $data['social_conversation_id'] : null,
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
            campaignId: isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            revenue: isset($data['revenue']) ? (float) $data['revenue'] : null,
            currency: $data['currency'] ?? 'USD',
            productOrService: $data['product_or_service'] ?? null,
            agentAttributionScore: (float) ($data['agent_attribution_score'] ?? 0.0),
            attributionPath: $data['attribution_path'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'conversion_type' => $this->conversionType,
            'actor_id' => $this->actorId,
            'social_lead_id' => $this->socialLeadId,
            'social_conversation_id' => $this->socialConversationId,
            'agent_deployment_id' => $this->agentDeploymentId,
            'campaign_id' => $this->campaignId,
            'revenue' => $this->revenue,
            'currency' => $this->currency,
            'product_or_service' => $this->productOrService,
            'agent_attribution_score' => $this->agentAttributionScore,
            'attribution_path' => $this->attributionPath,
            'metadata' => $this->metadata,
        ];
    }
}

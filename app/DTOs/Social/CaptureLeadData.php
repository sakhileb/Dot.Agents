<?php

namespace App\DTOs\Social;

readonly class CaptureLeadData
{
    public function __construct(
        public int $organizationId,
        public string $platform,
        public string $contactPlatformId,
        public ?int $socialConversationId = null,
        public ?int $agentDeploymentId = null,
        public ?string $contactName = null,
        public ?string $contactHandle = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $company = null,
        public ?string $jobTitle = null,
        public ?string $location = null,
        public string $intentLevel = 'browsing',
        public float $leadScore = 0.0,
        public float $intentScore = 0.0,
        public array $recommendedActions = [],
        public array $customFields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            platform: $data['platform'],
            contactPlatformId: $data['contact_platform_id'],
            socialConversationId: isset($data['social_conversation_id']) ? (int) $data['social_conversation_id'] : null,
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
            contactName: $data['contact_name'] ?? null,
            contactHandle: $data['contact_handle'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            company: $data['company'] ?? null,
            jobTitle: $data['job_title'] ?? null,
            location: $data['location'] ?? null,
            intentLevel: $data['intent_level'] ?? 'browsing',
            leadScore: (float) ($data['lead_score'] ?? 0.0),
            intentScore: (float) ($data['intent_score'] ?? 0.0),
            recommendedActions: $data['recommended_actions'] ?? [],
            customFields: $data['custom_fields'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'platform' => $this->platform,
            'contact_platform_id' => $this->contactPlatformId,
            'social_conversation_id' => $this->socialConversationId,
            'agent_deployment_id' => $this->agentDeploymentId,
            'contact_name' => $this->contactName,
            'contact_handle' => $this->contactHandle,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'job_title' => $this->jobTitle,
            'location' => $this->location,
            'intent_level' => $this->intentLevel,
            'lead_score' => $this->leadScore,
            'intent_score' => $this->intentScore,
            'recommended_actions' => $this->recommendedActions,
            'custom_fields' => $this->customFields,
        ];
    }
}

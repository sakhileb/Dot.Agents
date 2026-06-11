<?php

namespace App\DTOs\Social;

readonly class ConnectSocialAccountData
{
    public function __construct(
        public int $organizationId,
        public int $connectedBy,
        public string $platform,
        public string $platformAccountId,
        public string $accountName,
        public string $accessToken,
        public ?string $refreshToken = null,
        public ?string $accountHandle = null,
        public string $accountType = 'page',
        public ?string $avatarUrl = null,
        public ?\DateTimeInterface $tokenExpiresAt = null,
        public array $scopes = [],
        public array $settings = [],
        public ?int $agentDeploymentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            connectedBy: (int) $data['connected_by'],
            platform: $data['platform'],
            platformAccountId: $data['platform_account_id'],
            accountName: $data['account_name'],
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'] ?? null,
            accountHandle: $data['account_handle'] ?? null,
            accountType: $data['account_type'] ?? 'page',
            avatarUrl: $data['avatar_url'] ?? null,
            tokenExpiresAt: isset($data['token_expires_at']) ? new \DateTime($data['token_expires_at']) : null,
            scopes: $data['scopes'] ?? [],
            settings: $data['settings'] ?? [],
            agentDeploymentId: isset($data['agent_deployment_id']) ? (int) $data['agent_deployment_id'] : null,
        );
    }
}

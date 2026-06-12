<?php

namespace App\DTOs\Social;

readonly class SaveSocialCredentialsData
{
    public function __construct(
        public int $organizationId,
        public string $platform,
        public string $clientId,
        public string $clientSecret,
        public int $updatedBy,
        public ?string $redirectUri = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            platform: $data['platform'],
            clientId: $data['client_id'],
            clientSecret: $data['client_secret'],
            updatedBy: (int) $data['updated_by'],
            redirectUri: $data['redirect_uri'] ?? null,
        );
    }

    public function toCredentialsArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ];
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'platform' => $this->platform,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'updated_by' => $this->updatedBy,
            'redirect_uri' => $this->redirectUri,
        ];
    }
}

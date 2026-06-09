<?php

namespace App\DTOs\Compliance;

readonly class RecordConsentData
{
    public function __construct(
        public int $userId,
        public string $consentPurpose,
        public bool $granted,
        public string $version,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            consentPurpose: $data['consent_purpose'],
            granted: (bool) $data['granted'],
            version: $data['version'],
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }
}

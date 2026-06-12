<?php

declare(strict_types=1);

namespace App\DTOs\Social;

readonly class DisconnectSocialAccountData
{
    public function __construct(
        public string $platform,
    ) {}

    public static function fromPlatform(string $platform): self
    {
        return new self(platform: $platform);
    }
}

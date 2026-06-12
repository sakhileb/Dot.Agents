<?php

declare(strict_types=1);

namespace App\DTOs\Workflows;

readonly class UpdateWorkflowStatusData
{
    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PAUSED = 'paused';

    public function __construct(
        public string $status,
        public ?string $reason = null,
    ) {}

    public static function publish(?string $reason = null): self
    {
        return new self(status: self::STATUS_ACTIVE, reason: $reason);
    }

    public static function unpublish(?string $reason = null): self
    {
        return new self(status: self::STATUS_DRAFT, reason: $reason);
    }
}

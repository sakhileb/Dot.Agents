<?php

declare(strict_types=1);

namespace App\DTOs\Governance;

readonly class AuditLogExportParams
{
    public function __construct(
        public int $organizationId,
        public string $format = 'csv',
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public ?string $eventCategory = null,
        public ?string $riskLevel = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            organizationId: (int) $data['organization_id'],
            format: $data['format'] ?? 'csv',
            fromDate: $data['from_date'] ?? null,
            toDate: $data['to_date'] ?? null,
            eventCategory: $data['event_category'] ?? null,
            riskLevel: $data['risk_level'] ?? null,
        );
    }
}

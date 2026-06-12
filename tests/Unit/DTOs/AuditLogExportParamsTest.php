<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Governance\AuditLogExportParams;
use Tests\TestCase;

/**
 * Unit tests for AuditLogExportParams DTO.
 */
class AuditLogExportParamsTest extends TestCase
{
    public function test_can_construct_with_only_required_field(): void
    {
        $dto = new AuditLogExportParams(organizationId: 1);

        $this->assertSame(1, $dto->organizationId);
        $this->assertSame('csv', $dto->format);
        $this->assertNull($dto->fromDate);
        $this->assertNull($dto->toDate);
        $this->assertNull($dto->eventCategory);
        $this->assertNull($dto->riskLevel);
    }

    public function test_format_defaults_to_csv(): void
    {
        $dto = AuditLogExportParams::fromArray([
            'organization_id' => '1',
        ]);

        $this->assertSame('csv', $dto->format);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = AuditLogExportParams::fromArray([
            'organization_id' => '5',
            'format' => 'json',
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
            'event_category' => 'security',
            'risk_level' => 'high',
        ]);

        $this->assertSame(5, $dto->organizationId);
        $this->assertSame('json', $dto->format);
        $this->assertSame('2026-01-01', $dto->fromDate);
        $this->assertSame('2026-01-31', $dto->toDate);
        $this->assertSame('security', $dto->eventCategory);
        $this->assertSame('high', $dto->riskLevel);
    }

    public function test_from_array_optional_fields_default_to_null(): void
    {
        $dto = AuditLogExportParams::fromArray([
            'organization_id' => '1',
        ]);

        $this->assertNull($dto->fromDate);
        $this->assertNull($dto->toDate);
        $this->assertNull($dto->eventCategory);
        $this->assertNull($dto->riskLevel);
    }

    public function test_from_array_casts_organization_id_to_integer(): void
    {
        $dto = AuditLogExportParams::fromArray([
            'organization_id' => '42',
        ]);

        $this->assertSame(42, $dto->organizationId);
    }
}

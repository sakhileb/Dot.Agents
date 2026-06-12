<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Agents\UpdateDeploymentData;
use Tests\TestCase;

/**
 * Unit tests for UpdateDeploymentData DTO.
 */
class UpdateDeploymentDataTest extends TestCase
{
    public function test_all_fields_are_nullable_by_default(): void
    {
        $dto = new UpdateDeploymentData;

        $this->assertNull($dto->name);
        $this->assertNull($dto->deploymentMode);
        $this->assertNull($dto->confidenceThreshold);
        $this->assertNull($dto->customInstructions);
        $this->assertNull($dto->departmentId);
        $this->assertNull($dto->teamId);
        $this->assertNull($dto->isActive);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = UpdateDeploymentData::fromArray([
            'name' => 'New Name',
            'deployment_mode' => 'advisory',
            'confidence_threshold' => '85.5',
            'custom_instructions' => 'Be concise',
            'department_id' => '2',
            'team_id' => '5',
            'is_active' => '1',
        ]);

        $this->assertSame('New Name', $dto->name);
        $this->assertSame('advisory', $dto->deploymentMode);
        $this->assertSame(85.5, $dto->confidenceThreshold);
        $this->assertSame('Be concise', $dto->customInstructions);
        $this->assertSame(2, $dto->departmentId);
        $this->assertSame(5, $dto->teamId);
        $this->assertTrue($dto->isActive);
    }

    public function test_from_array_missing_keys_default_to_null(): void
    {
        $dto = UpdateDeploymentData::fromArray([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->deploymentMode);
        $this->assertNull($dto->confidenceThreshold);
    }

    public function test_to_array_excludes_null_values(): void
    {
        $dto = new UpdateDeploymentData(name: 'Only Name');

        $array = $dto->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertSame('Only Name', $array['name']);
        // All null fields must be stripped by array_filter
        $this->assertArrayNotHasKey('deployment_mode', $array);
        $this->assertArrayNotHasKey('confidence_threshold', $array);
        $this->assertArrayNotHasKey('custom_instructions', $array);
        $this->assertArrayNotHasKey('department_id', $array);
        $this->assertArrayNotHasKey('team_id', $array);
        $this->assertArrayNotHasKey('is_active', $array);
    }

    public function test_to_array_returns_empty_when_all_null(): void
    {
        $dto = new UpdateDeploymentData;

        $this->assertEmpty($dto->toArray());
    }
}

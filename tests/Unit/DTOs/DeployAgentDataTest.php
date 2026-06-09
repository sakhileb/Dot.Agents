<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Agents\DeployAgentData;
use PHPUnit\Framework\TestCase;

class DeployAgentDataTest extends TestCase
{
    public function test_creates_from_array_with_all_fields(): void
    {
        $data = DeployAgentData::fromArray([
            'agent_id' => 1,
            'organization_id' => 2,
            'deployed_by' => 3,
            'name' => 'Test Agent',
            'deployment_mode' => 'advisory',
            'confidence_threshold' => 85.0,
            'department_id' => 10,
            'custom_instructions' => 'Be concise.',
        ]);

        $this->assertEquals(1, $data->agentId);
        $this->assertEquals(2, $data->organizationId);
        $this->assertEquals(3, $data->deployedBy);
        $this->assertEquals('Test Agent', $data->name);
        $this->assertEquals('advisory', $data->deploymentMode);
        $this->assertEquals(85.0, $data->confidenceThreshold);
        $this->assertEquals(10, $data->departmentId);
        $this->assertEquals('Be concise.', $data->customInstructions);
    }

    public function test_advisory_mode_sets_requires_human_approval_true(): void
    {
        $data = DeployAgentData::fromArray([
            'agent_id' => 1,
            'organization_id' => 2,
            'deployed_by' => 3,
            'name' => 'Test',
            'deployment_mode' => 'advisory',
        ]);

        $this->assertTrue($data->requiresHumanApproval);
    }

    public function test_autonomous_mode_sets_requires_human_approval_false(): void
    {
        $data = DeployAgentData::fromArray([
            'agent_id' => 1,
            'organization_id' => 2,
            'deployed_by' => 3,
            'name' => 'Test',
            'deployment_mode' => 'autonomous',
        ]);

        $this->assertFalse($data->requiresHumanApproval);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $data = new DeployAgentData(
            agentId: 1,
            organizationId: 2,
            deployedBy: 3,
            name: 'Test',
            deploymentMode: 'advisory',
            confidenceThreshold: 75.0,
        );
        $array = $data->toArray();

        foreach (['agent_id', 'organization_id', 'deployed_by', 'name', 'deployment_mode', 'confidence_threshold'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    public function test_confidence_threshold_defaults_to_75(): void
    {
        $data = DeployAgentData::fromArray([
            'agent_id' => 1,
            'organization_id' => 2,
            'deployed_by' => 3,
            'name' => 'Test',
            'deployment_mode' => 'advisory',
        ]);

        $this->assertEquals(75.0, $data->confidenceThreshold);
    }

    public function test_dto_is_readonly(): void
    {
        $data = new DeployAgentData(
            agentId: 1, organizationId: 2, deployedBy: 3,
            name: 'Test', deploymentMode: 'advisory'
        );

        $this->expectException(\Error::class);
        $data->agentId = 99;
    }
}

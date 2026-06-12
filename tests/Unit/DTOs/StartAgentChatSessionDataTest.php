<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Agents\StartAgentChatSessionData;
use Tests\TestCase;

/**
 * Unit tests for StartAgentChatSessionData DTO.
 */
class StartAgentChatSessionDataTest extends TestCase
{
    public function test_can_construct_with_required_fields(): void
    {
        $dto = new StartAgentChatSessionData(
            userId: 1,
            agentDeploymentId: 2,
        );

        $this->assertSame(1, $dto->userId);
        $this->assertSame(2, $dto->agentDeploymentId);
        $this->assertNull($dto->organizationId);
        $this->assertNull($dto->title);
        $this->assertNull($dto->initialMessage);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = StartAgentChatSessionData::fromArray([
            'user_id' => '5',
            'agent_deployment_id' => '10',
            'organization_id' => '3',
            'title' => 'Q4 Planning',
            'initial_message' => 'Hello agent!',
        ]);

        $this->assertSame(5, $dto->userId);
        $this->assertSame(10, $dto->agentDeploymentId);
        $this->assertSame(3, $dto->organizationId);
        $this->assertSame('Q4 Planning', $dto->title);
        $this->assertSame('Hello agent!', $dto->initialMessage);
    }

    public function test_from_array_optional_fields_default_to_null(): void
    {
        $dto = StartAgentChatSessionData::fromArray([
            'user_id' => '1',
            'agent_deployment_id' => '2',
        ]);

        $this->assertNull($dto->organizationId);
        $this->assertNull($dto->title);
        $this->assertNull($dto->initialMessage);
    }

    public function test_to_array_excludes_null_values(): void
    {
        $dto = new StartAgentChatSessionData(
            userId: 1,
            agentDeploymentId: 2,
        );

        $array = $dto->toArray();

        // Only non-null values should appear
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('agent_deployment_id', $array);
        $this->assertArrayNotHasKey('organization_id', $array);
        $this->assertArrayNotHasKey('title', $array);
        $this->assertArrayNotHasKey('initial_message', $array);
    }

    public function test_to_array_includes_all_non_null_values(): void
    {
        $dto = new StartAgentChatSessionData(
            userId: 1,
            agentDeploymentId: 2,
            organizationId: 3,
            title: 'Session',
            initialMessage: 'Hi',
        );

        $array = $dto->toArray();

        $this->assertSame(1, $array['user_id']);
        $this->assertSame(2, $array['agent_deployment_id']);
        $this->assertSame(3, $array['organization_id']);
        $this->assertSame('Session', $array['title']);
        $this->assertSame('Hi', $array['initial_message']);
    }
}

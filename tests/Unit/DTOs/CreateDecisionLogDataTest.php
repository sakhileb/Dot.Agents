<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Governance\CreateDecisionLogData;
use Tests\TestCase;

/**
 * Unit tests for CreateDecisionLogData DTO.
 */
class CreateDecisionLogDataTest extends TestCase
{
    public function test_constructor_sets_all_fields(): void
    {
        $dto = new CreateDecisionLogData(
            agentDeploymentId: 1,
            agentTaskId: 2,
            output: ['result' => 'processed'],
            context: ['source' => 'test'],
        );

        $this->assertSame(1, $dto->agentDeploymentId);
        $this->assertSame(2, $dto->agentTaskId);
        $this->assertSame(['result' => 'processed'], $dto->output);
        $this->assertSame(['source' => 'test'], $dto->context);
    }

    public function test_context_defaults_to_empty_array(): void
    {
        $dto = new CreateDecisionLogData(
            agentDeploymentId: 1,
            agentTaskId: 2,
            output: ['result' => 'ok'],
        );

        $this->assertSame([], $dto->context);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = CreateDecisionLogData::fromArray([
            'agent_deployment_id' => '5',
            'agent_task_id' => '10',
            'output' => ['answer' => '42'],
            'context' => ['session_id' => 'abc'],
        ]);

        $this->assertSame(5, $dto->agentDeploymentId);
        $this->assertSame(10, $dto->agentTaskId);
        $this->assertSame(['answer' => '42'], $dto->output);
        $this->assertSame(['session_id' => 'abc'], $dto->context);
    }

    public function test_from_array_context_defaults_to_empty_array(): void
    {
        $dto = CreateDecisionLogData::fromArray([
            'agent_deployment_id' => '1',
            'agent_task_id' => '2',
            'output' => [],
        ]);

        $this->assertSame([], $dto->context);
    }

    public function test_from_array_casts_string_ids_to_integers(): void
    {
        $dto = CreateDecisionLogData::fromArray([
            'agent_deployment_id' => '99',
            'agent_task_id' => '100',
            'output' => [],
        ]);

        $this->assertSame(99, $dto->agentDeploymentId);
        $this->assertSame(100, $dto->agentTaskId);
    }

    public function test_output_can_be_empty_array(): void
    {
        $dto = CreateDecisionLogData::fromArray([
            'agent_deployment_id' => '1',
            'agent_task_id' => '1',
            'output' => [],
        ]);

        $this->assertSame([], $dto->output);
    }
}

<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Skills\ExecuteSkillData;
use Tests\TestCase;

/**
 * Unit tests for ExecuteSkillData DTO.
 */
class ExecuteSkillDataTest extends TestCase
{
    private array $minimal = [
        'skill_id' => '10',
        'agent_deployment_id' => '5',
        'organization_id' => '2',
        'actor_id' => '1',
        'trigger' => 'on_demand',
    ];

    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = ExecuteSkillData::fromArray($this->minimal);

        $this->assertSame(10, $dto->skillId);
        $this->assertSame(5, $dto->agentDeploymentId);
        $this->assertSame(2, $dto->organizationId);
        $this->assertSame(1, $dto->actorId);
        $this->assertSame('on_demand', $dto->trigger);
    }

    public function test_from_array_input_defaults_to_empty_array(): void
    {
        $dto = ExecuteSkillData::fromArray($this->minimal);

        $this->assertSame([], $dto->input);
    }

    public function test_from_array_task_id_defaults_to_null(): void
    {
        $dto = ExecuteSkillData::fromArray($this->minimal);

        $this->assertNull($dto->taskId);
    }

    public function test_from_array_justification_defaults_to_null(): void
    {
        $dto = ExecuteSkillData::fromArray($this->minimal);

        $this->assertNull($dto->justification);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = ExecuteSkillData::fromArray(array_merge($this->minimal, [
            'input' => ['query' => 'what is revenue today'],
            'task_id' => '99',
            'justification' => 'Automated reporting',
        ]));

        $this->assertSame(['query' => 'what is revenue today'], $dto->input);
        $this->assertSame(99, $dto->taskId);
        $this->assertSame('Automated reporting', $dto->justification);
    }

    public function test_to_array_returns_all_keys(): void
    {
        $dto = ExecuteSkillData::fromArray($this->minimal);
        $array = $dto->toArray();

        foreach (['skill_id', 'agent_deployment_id', 'organization_id', 'actor_id', 'trigger', 'input', 'task_id', 'justification'] as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $dto = ExecuteSkillData::fromArray(array_merge($this->minimal, [
            'input' => ['param' => 'value'],
            'task_id' => '7',
            'justification' => 'Reason',
        ]));

        $array = $dto->toArray();

        $this->assertSame(10, $array['skill_id']);
        $this->assertSame(5, $array['agent_deployment_id']);
        $this->assertSame(['param' => 'value'], $array['input']);
        $this->assertSame(7, $array['task_id']);
        $this->assertSame('Reason', $array['justification']);
    }

    public function test_to_array_trigger_is_preserved(): void
    {
        foreach (['on_demand', 'scheduled', 'delegated', 'pre_task', 'post_task'] as $trigger) {
            $dto = ExecuteSkillData::fromArray(array_merge($this->minimal, ['trigger' => $trigger]));
            $this->assertSame($trigger, $dto->toArray()['trigger']);
        }
    }
}

<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentSkillExecutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'skill_id' => AgentSkill::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'task_id' => null,
            'trigger' => $this->faker->randomElement(['manual', 'scheduled', 'event']),
            'status' => $this->faker->randomElement(['running', 'completed', 'failed', 'pending']),
            'input' => [],
            'output' => null,
            'findings' => null,
            'confidence' => $this->faker->randomFloat(1, 60, 95),
            'duration_ms' => $this->faker->numberBetween(100, 5000),
            'error' => null,
            'executed_at' => now(),
        ];
    }
}

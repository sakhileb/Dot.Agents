<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentSkillScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'skill_id' => AgentSkill::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'period' => now()->format('Y-m'),
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'blocked_executions' => 0,
            'success_rate' => 0.0,
            'avg_confidence' => null,
            'avg_duration_ms' => null,
        ];
    }
}

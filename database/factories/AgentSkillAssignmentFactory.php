<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentSkillAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agent_deployment_id' => AgentDeployment::factory(),
            'skill_id' => AgentSkill::factory(),
            'organization_id' => Organization::factory(),
            'is_enabled' => true,
            'config' => null,
        ];
    }
}

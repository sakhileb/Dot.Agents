<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentSkillApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'skill_id' => AgentSkill::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'execution_id' => null,
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'status' => 'pending',
            'risk_level' => 'low',
            'context' => null,
            'justification' => $this->faker->sentence(),
            'reviewer_notes' => null,
            'expires_at' => now()->addHours(24),
            'reviewed_at' => null,
        ];
    }
}

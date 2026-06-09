<?php

namespace Database\Factories;

use App\Models\AgentApproval;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentApproval>
 */
class AgentApprovalFactory extends Factory
{
    protected $model = AgentApproval::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'task_id' => AgentTask::factory(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'requested_from' => User::factory(),
            'approval_type' => fake()->randomElement(['task_execution', 'data_access', 'action_authorization']),
            'title' => 'Approval Required: '.fake()->sentence(5),
            'description' => fake()->paragraph(),
            'proposed_action' => ['action' => fake()->sentence(), 'impact' => fake()->sentence()],
            'impact_assessment' => ['risk' => 'medium', 'affected_systems' => []],
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'confidence_score' => fake()->randomFloat(2, 50, 95),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewer_notes' => null,
            'reviewer_data' => null,
            'reviewed_at' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'reviewed_at' => null]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewer_notes' => 'Approved — looks good.',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewer_notes' => fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }

    public function highRisk(): static
    {
        return $this->state([
            'risk_level' => 'high',
            'confidence_score' => fake()->randomFloat(2, 40, 65),
        ]);
    }
}

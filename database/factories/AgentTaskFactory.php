<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentTask>
 */
class AgentTaskFactory extends Factory
{
    protected $model = AgentTask::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'session_id' => null,
            'assigned_by' => User::factory(),
            'parent_task_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'task_type' => fake()->randomElement(['analysis', 'report', 'action', 'review']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'pending',
            'input_data' => ['query' => fake()->sentence()],
            'output_data' => null,
            'result_summary' => null,
            'artifacts' => null,
            'confidence_score' => null,
            'accuracy_score' => null,
            'risk_score' => 0.0,
            'delusion_risk_score' => 0.0,
            'reality_alignment_score' => 100.0,
            'estimated_duration_minutes' => fake()->numberBetween(1, 60),
            'actual_duration_minutes' => null,
            'token_count' => 0,
            'cost' => 0,
            'due_at' => now()->addHours(24),
            'started_at' => null,
            'completed_at' => null,
            'metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'confidence_score' => fake()->randomFloat(2, 70, 99),
            'accuracy_score' => fake()->randomFloat(2, 70, 99),
            'risk_score' => fake()->randomFloat(2, 0, 40),
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'token_count' => fake()->numberBetween(500, 5000),
            'cost' => fake()->randomFloat(4, 0.01, 2.0),
            'output_data' => [
                'summary' => fake()->paragraph(),
                'confidence' => fake()->randomFloat(2, 70, 99),
                'actions' => [],
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    public function highRisk(): static
    {
        return $this->state([
            'risk_score' => fake()->randomFloat(2, 70, 100),
            'delusion_risk_score' => fake()->randomFloat(2, 60, 100),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentScorecardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'period' => 'monthly',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'accuracy_score' => $this->faker->randomFloat(2, 60, 99),
            'productivity_score' => $this->faker->randomFloat(2, 60, 99),
            'compliance_score' => $this->faker->randomFloat(2, 70, 99),
            'reliability_score' => $this->faker->randomFloat(2, 60, 99),
            'trustworthiness_score' => $this->faker->randomFloat(2, 65, 99),
            'cost_savings_score' => $this->faker->randomFloat(2, 50, 95),
            'revenue_impact_score' => $this->faker->randomFloat(2, 50, 95),
            'risk_impact_score' => $this->faker->randomFloat(2, 60, 99),
            'user_satisfaction_score' => $this->faker->randomFloat(2, 65, 99),
            'learning_rate_score' => $this->faker->randomFloat(2, 55, 90),
            'overall_health_score' => $this->faker->randomFloat(2, 60, 95),
            'tasks_completed' => $this->faker->numberBetween(10, 500),
            'tasks_failed' => $this->faker->numberBetween(0, 20),
            'decisions_made' => $this->faker->numberBetween(5, 200),
            'decisions_overridden' => $this->faker->numberBetween(0, 10),
            'hallucinations_detected' => $this->faker->numberBetween(0, 5),
            'approvals_requested' => $this->faker->numberBetween(0, 30),
            'approvals_granted' => $this->faker->numberBetween(0, 25),
            'total_cost' => $this->faker->randomFloat(4, 1, 100),
            'estimated_savings' => $this->faker->randomFloat(2, 10, 500),
            'estimated_revenue_impact' => $this->faker->randomFloat(2, 0, 1000),
            'total_tokens_used' => $this->faker->numberBetween(1000, 500000),
            'avg_response_time_ms' => $this->faker->randomFloat(2, 200, 3000),
            'uptime_percentage' => $this->faker->randomFloat(2, 95, 100),
            'detailed_metrics' => [],
            'recommendations' => [],
        ];
    }
}

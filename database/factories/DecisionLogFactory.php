<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\DecisionLog;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DecisionLogFactory extends Factory
{
    protected $model = DecisionLog::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'agent_deployment_id' => AgentDeployment::factory(),
            'organization_id' => Organization::factory(),
            'decision_type' => $this->faker->randomElement(['recommendation', 'action', 'analysis', 'communication', 'risk_flag']),
            'title' => $this->faker->sentence(4),
            'decision_summary' => $this->faker->paragraph(),
            'confidence_score' => $this->faker->randomFloat(2, 50, 100),
            'risk_score' => $this->faker->randomFloat(2, 0, 50),
            'impact_score' => $this->faker->randomFloat(2, 0, 100),
            'delusion_risk_score' => $this->faker->randomFloat(2, 0, 30),
            'reality_alignment_score' => $this->faker->randomFloat(2, 70, 100),
            'verification_score' => $this->faker->randomFloat(2, 70, 100),
            'evidence_quality_score' => $this->faker->randomFloat(2, 70, 100),
            'source_credibility_score' => $this->faker->randomFloat(2, 70, 100),
            'delusion_analysis' => null,
            'requires_human_review' => false,
            'human_reviewed' => false,
            'compliance_checked' => true,
            'compliance_passed' => true,
        ];
    }

    public function highDelusionRisk(): self
    {
        return $this->state([
            'delusion_risk_score' => $this->faker->randomFloat(2, 70, 100),
            'reality_alignment_score' => $this->faker->randomFloat(2, 10, 40),
            'requires_human_review' => true,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $name = fake()->jobTitle().' Agent';

        return [
            'uuid' => (string) Str::uuid(),
            'category_id' => AgentCategory::factory(),
            'department_id' => AgentDepartment::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'tagline' => fake()->sentence(8),
            'description' => fake()->paragraph(2),
            'full_description' => fake()->paragraphs(4, true),
            'avatar' => null,
            'version' => '1.0.0',
            'agent_type' => fake()->randomElement(['assistant', 'analyst', 'executor', 'monitor']),
            'specialization' => fake()->words(3, true),
            'industries' => fake()->randomElements(['Finance', 'Technology', 'Healthcare'], 2),
            'functions' => fake()->randomElements(['Analysis', 'Reporting', 'Automation'], 2),
            'primary_model' => 'gpt-4o',
            'model_provider' => 'openai',
            'fallback_model' => 'gpt-4o-mini',
            'model_config' => ['temperature' => 0.7, 'max_tokens' => 2000],
            'capabilities' => [fake()->sentence(), fake()->sentence()],
            'limitations' => [fake()->sentence()],
            'skills' => ['Analysis', 'Reporting'],
            'tools' => [],
            'integrations' => [],
            'knowledge_areas' => [],
            'certifications' => [],
            'languages' => ['en'],
            'goals' => [],
            'objectives' => [],
            'kpis' => [],
            'decision_framework' => [],
            'risk_controls' => [],
            'default_deployment_mode' => 'advisory',
            'pricing_model' => 'per_task',
            'base_price' => fake()->randomFloat(2, 0, 199),
            'price_per_message' => fake()->randomFloat(4, 0.001, 0.05),
            'price_per_task' => fake()->randomFloat(2, 0.5, 10),
            'billing_cycle' => 'monthly',
            'accuracy_score' => fake()->randomFloat(2, 70, 99),
            'reliability_score' => fake()->randomFloat(2, 80, 99),
            'satisfaction_score' => fake()->randomFloat(2, 70, 99),
            'total_deployments' => fake()->numberBetween(0, 500),
            'total_tasks_completed' => fake()->numberBetween(0, 10000),
            'avg_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'review_count' => fake()->numberBetween(0, 200),
            'status' => 'active',
            'is_featured' => false,
            'is_verified' => true,
            'is_enterprise_only' => false,
            'is_beta' => false,
            'required_plan' => ['starter'],
            'tags' => fake()->words(3),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function autonomous(): static
    {
        return $this->state(['default_deployment_mode' => 'autonomous']);
    }
}

<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentReview;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentReviewFactory extends Factory
{
    protected $model = AgentReview::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'deployment_id' => null,
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'dimension_scores' => null,
            'is_verified' => false,
            'helpful_count' => 0,
            'is_featured' => false,
        ];
    }
}

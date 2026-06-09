<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentPersonaFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'agent_id' => Agent::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'avatar' => null,
            'system_prompt' => 'You are a helpful AI assistant.',
            'tone' => $this->faker->randomElement(['professional', 'friendly', 'concise', 'detailed']),
            'personality_traits' => [],
            'communication_style' => 'clear',
            'response_format' => [],
            'language' => 'en',
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}

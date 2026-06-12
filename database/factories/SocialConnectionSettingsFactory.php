<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SocialConnectionSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialConnectionSettingsFactory extends Factory
{
    protected $model = SocialConnectionSettings::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'social_account_id' => null,
            'platform' => fake()->randomElement(['facebook', 'instagram', 'linkedin', 'twitter']),
            'goals' => ['generate_leads'],
            'ai_features' => ['customer_support'],
            'permissions' => ['reply_comments'],
            'autonomy_level' => 1,
            'status' => 'active',
        ];
    }
}

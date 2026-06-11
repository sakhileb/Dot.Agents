<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'agent_deployment_id' => null,
            'platform' => fake()->randomElement(['facebook', 'instagram', 'linkedin', 'x', 'whatsapp', 'telegram', 'youtube', 'tiktok']),
            'platform_account_id' => fake()->numerify('platform_########'),
            'account_name' => fake()->company(),
            'account_handle' => '@'.fake()->userName(),
            'account_type' => 'page',
            'avatar_url' => null,
            'access_token' => Str::random(64),
            'refresh_token' => Str::random(64),
            'token_expires_at' => now()->addDays(60),
            'scopes' => ['read', 'write'],
            'settings' => [],
            'status' => 'active',
            'is_primary' => false,
            'connected_at' => now(),
            'last_synced_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationSocialCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationSocialCredentialFactory extends Factory
{
    protected $model = OrganizationSocialCredential::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'platform' => fake()->randomElement(['facebook', 'instagram', 'linkedin', 'twitter', 'tiktok']),
            'client_id' => fake()->uuid(),
            'client_secret' => fake()->sha256(),
            'redirect_uri' => null,
            'extra_config' => null,
            'updated_by' => null,
        ];
    }
}

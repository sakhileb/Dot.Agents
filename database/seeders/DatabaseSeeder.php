<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();

        User::factory()->withPersonalTeam()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@dotagents.com',
            'password' => Hash::make('DotAgents2024!'),
        ]);

        $this->call([
            AgentPlatformSeeder::class,
        ]);
    }
}

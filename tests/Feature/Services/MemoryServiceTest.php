<?php

namespace Tests\Feature\Services;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgentDeployment $deployment;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $org = Organization::factory()->create(['owner_id' => $user->id]);
        session(['current_organization_id' => $org->id]);
        Gate::before(fn () => true);

        $this->deployment = AgentDeployment::factory()->create([
            'organization_id' => $org->id,
            'enable_memory' => true,
        ]);
    }

    public function test_get_relevant_memories_returns_array(): void
    {
        $memories = app(MemoryService::class)
            ->getRelevantMemories($this->deployment, 'revenue analysis', 5);

        $this->assertIsArray($memories);
    }

    public function test_get_relevant_memories_respects_limit(): void
    {
        // Create memories
        for ($i = 0; $i < 10; $i++) {
            AgentMemory::factory()->create([
                'agent_deployment_id' => $this->deployment->id,
                'content' => "Memory item $i about revenue",
                'is_active' => true,
            ]);
        }

        $memories = app(MemoryService::class)
            ->getRelevantMemories($this->deployment, 'revenue', 3);

        $this->assertLessThanOrEqual(3, count($memories));
    }

    public function test_get_relevant_memories_returns_empty_when_no_memories(): void
    {
        $memories = app(MemoryService::class)
            ->getRelevantMemories($this->deployment, 'anything');

        $this->assertEmpty($memories);
    }
}

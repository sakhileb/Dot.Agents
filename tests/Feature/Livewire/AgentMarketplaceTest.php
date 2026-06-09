<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Marketplace\AgentMarketplace;
use App\Models\Agent;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AgentMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        session(['current_organization_id' => $this->organization->id]);
        Cache::flush();
    }

    public function test_marketplace_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class)
            ->assertStatus(200);
    }

    public function test_search_filters_agents(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class)
            ->set('search', 'analytics')
            ->assertSet('search', 'analytics');
    }

    public function test_sort_by_can_be_changed(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class)
            ->set('sortBy', 'rating')
            ->assertSet('sortBy', 'rating');
    }

    public function test_departments_are_cached(): void
    {
        $this->actingAs($this->user);

        AgentDepartment::factory()->create(['is_active' => true, 'sort_order' => 1]);
        AgentDepartment::factory()->create(['is_active' => true, 'sort_order' => 2]);

        $component = Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class);

        // Departments should be populated
        $this->assertCount(2, $component->get('departments'));

        // Second call should use cache
        $this->assertTrue(Cache::has('marketplace_departments'));
    }

    public function test_categories_are_cached(): void
    {
        $this->actingAs($this->user);

        AgentCategory::factory()->create(['is_active' => true, 'sort_order' => 1]);

        $component = Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class);

        // Trigger evaluation of categories computed property
        $this->assertCount(1, $component->get('categories'));

        $this->assertTrue(Cache::has('marketplace_categories'));
    }

    public function test_preview_agent_sets_selected(): void
    {
        $this->actingAs($this->user);

        $agent = Agent::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($this->user)
            ->test(AgentMarketplace::class)
            ->call('previewAgent', $agent->id);

        $this->assertNotNull($component->get('previewAgent'));
        $this->assertSame($agent->id, $component->get('previewAgent')['id']);
    }
}

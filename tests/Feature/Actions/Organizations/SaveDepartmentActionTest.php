<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\SaveDepartmentAction;
use App\DTOs\Organizations\SaveDepartmentData;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaveDepartmentActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->actingAs($this->user);
        Gate::before(fn () => true);
        session(['current_organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_creates_new_department(): void
    {
        $data = new SaveDepartmentData(
            name: 'Engineering',
            description: 'Product engineering team',
            type: 'technical',
        );

        $dept = app(SaveDepartmentAction::class)->execute($this->organization, $data);

        $this->assertInstanceOf(Department::class, $dept);
        $this->assertEquals('Engineering', $dept->name);
        $this->assertEquals($this->organization->id, $dept->organization_id);
        $this->assertDatabaseHas('departments', ['name' => 'Engineering', 'organization_id' => $this->organization->id]);
    }

    #[Test]
    public function test_updates_existing_department(): void
    {
        $existing = Department::create([
            'organization_id' => $this->organization->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
            'is_active' => true,
        ]);

        $data = new SaveDepartmentData(
            name: 'New Name',
            existingId: $existing->id,
        );

        $result = app(SaveDepartmentAction::class)->execute($this->organization, $data);

        $this->assertEquals('New Name', $result->name);
        $this->assertDatabaseHas('departments', ['id' => $existing->id, 'name' => 'New Name']);
    }
}

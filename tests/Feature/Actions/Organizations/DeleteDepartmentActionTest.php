<?php

namespace Tests\Feature\Actions\Organizations;

use App\Actions\Organizations\DeleteDepartmentAction;
use App\DTOs\Organizations\DeleteDepartmentData;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteDepartmentActionTest extends TestCase
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
    public function test_deletes_department(): void
    {
        $dept = Department::create([
            'organization_id' => $this->organization->id,
            'name' => 'HR',
            'slug' => 'hr',
            'is_active' => true,
        ]);

        $data = DeleteDepartmentData::fromId($dept->id);
        app(DeleteDepartmentAction::class)->execute($this->organization, $data);

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }
}

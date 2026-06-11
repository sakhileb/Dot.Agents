<?php

namespace App\Actions\Organizations;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

class DeleteDepartmentAction
{
    public function execute(Organization $organization, int $departmentId): void
    {
        Gate::authorize('update', $organization);

        $department = Department::where('organization_id', $organization->id)
            ->findOrFail($departmentId);

        $department->delete();
    }
}

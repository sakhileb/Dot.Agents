<?php

namespace App\Actions\Organizations;

use App\Models\Department;
use App\Models\Organization;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveDepartmentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(Organization $organization, array $data, ?int $existingId = null): Department
    {
        Gate::authorize('update', $organization);

        $payload = [
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
            'head_name' => $data['head_name'] ?? null,
            'is_active' => true,
        ];

        if ($existingId) {
            $dept = Department::findOrFail($existingId);
            abort_if($dept->organization_id !== $organization->id, 403);
            $dept->update($payload);
            $dept = $dept->fresh();
        } else {
            $dept = Department::create($payload);
        }

        $this->auditService->logUserAction(
            event: $existingId ? 'department.updated' : 'department.created',
            description: ($existingId ? 'Updated' : 'Created')." department '{$dept->name}'",
            subject: $dept,
        );

        return $dept;
    }
}

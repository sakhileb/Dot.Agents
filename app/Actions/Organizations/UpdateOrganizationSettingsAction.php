<?php

namespace App\Actions\Organizations;

use App\Events\OrganizationSettingsUpdated;
use App\Models\Organization;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class UpdateOrganizationSettingsAction
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function execute(Organization $organization, array $data): Organization
    {
        Gate::authorize('update', $organization);

        $allowed = [
            'name', 'domain', 'logo', 'industry', 'size',
            'country', 'timezone', 'currency', 'settings', 'billing_address',
        ];

        $old = $organization->only($allowed);
        $updates = array_intersect_key($data, array_flip($allowed));

        if (isset($updates['settings'])) {
            $updates['settings'] = array_merge(
                $organization->settings ?? [],
                $updates['settings']
            );
        }

        $organization->update($updates);

        event(new OrganizationSettingsUpdated($organization, ['old' => $old, 'new' => $updates]));

        return $organization->refresh();
    }
}

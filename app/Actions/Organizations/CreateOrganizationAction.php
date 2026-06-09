<?php

namespace App\Actions\Organizations;

use App\DTOs\Organizations\CreateOrganizationData;
use App\Events\OrganizationCreated;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function execute(CreateOrganizationData $data, User $owner): Organization
    {
        Gate::forUser($owner)->authorize('create', Organization::class);

        $organization = Organization::create([
            'name' => $data->name,
            'slug' => Str::slug($data->name).'-'.Str::random(4),
            'industry' => $data->industry,
            'size' => $data->size,
            'plan' => 'starter',
            'owner_id' => $owner->id,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $organization->users()->attach($owner->id, [
            'role' => 'owner',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        event(new OrganizationCreated($organization));

        return $organization;
    }
}

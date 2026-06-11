<?php

namespace App\Actions\Workflows;

use App\Models\AgentWorkflow;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateWorkflowAction
{
    public function execute(Organization $organization, array $data): AgentWorkflow
    {
        Gate::authorize('update', $organization);

        return AgentWorkflow::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'created_by' => Auth::id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_type' => $data['trigger_type'],
            'status' => 'draft',
        ]);
    }
}

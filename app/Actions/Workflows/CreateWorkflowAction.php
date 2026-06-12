<?php

namespace App\Actions\Workflows;

use App\DTOs\Workflows\CreateWorkflowData;
use App\Events\WorkflowCreated;
use App\Models\AgentWorkflow;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateWorkflowAction
{
    public function execute(Organization $organization, CreateWorkflowData $data): AgentWorkflow
    {
        Gate::authorize('update', $organization);

        $workflow = AgentWorkflow::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'created_by' => Auth::id(),
            'name' => $data->name,
            'description' => $data->description,
            'trigger_type' => $data->triggerType,
            'status' => 'draft',
        ]);

        event(new WorkflowCreated($workflow));

        return $workflow;
    }
}

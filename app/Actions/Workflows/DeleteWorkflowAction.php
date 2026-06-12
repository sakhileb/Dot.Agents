<?php

declare(strict_types=1);

namespace App\Actions\Workflows;

use App\Events\WorkflowDeleted;
use App\Models\AgentWorkflow;
use Illuminate\Support\Facades\Gate;

class DeleteWorkflowAction
{
    public function execute(AgentWorkflow $workflow): void
    {
        Gate::authorize('delete', $workflow);

        $id = $workflow->id;
        $orgId = $workflow->organization_id;
        $name = $workflow->name;

        $workflow->delete();

        event(new WorkflowDeleted($id, $orgId, $name));
    }
}

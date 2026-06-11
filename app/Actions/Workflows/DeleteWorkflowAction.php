<?php

namespace App\Actions\Workflows;

use App\Models\AgentWorkflow;
use Illuminate\Support\Facades\Gate;

class DeleteWorkflowAction
{
    public function execute(AgentWorkflow $workflow): void
    {
        Gate::authorize('delete', $workflow);

        $workflow->delete();
    }
}

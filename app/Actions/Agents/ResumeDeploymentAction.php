<?php

namespace App\Actions\Agents;

use App\Events\AgentResumed;
use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class ResumeDeploymentAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function execute(AgentDeployment $deployment): AgentDeployment
    {
        Gate::authorize('update', $deployment);

        $deployment->update(['status' => 'active']);

        event(new AgentResumed($deployment));

        return $deployment->refresh();
    }
}

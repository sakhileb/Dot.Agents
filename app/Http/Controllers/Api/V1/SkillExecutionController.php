<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Skills\ExecuteSkillAction;
use App\DTOs\Skills\ExecuteSkillData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteSkillRequest;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Services\Skills\SkillQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles skill execution and performance score retrieval for a deployment.
 *
 * Assignment management → DeploymentSkillController
 * Catalog browsing → SkillCatalogController
 */
class SkillExecutionController extends Controller
{
    public function execute(
        ExecuteSkillRequest $request,
        AgentDeployment $deployment,
        AgentSkill $skill,
        ExecuteSkillAction $action
    ): JsonResponse {
        $this->authorize('view', $deployment);

        $orgId = session('current_organization_id');

        $data = ExecuteSkillData::fromArray([
            'skill_id' => $skill->id,
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $orgId,
            'actor_id' => $request->user()->id,
            'trigger' => $request->input('trigger'),
            'input' => $request->input('input', []),
            'task_id' => $request->input('task_id'),
            'justification' => $request->input('justification'),
        ]);

        $execution = $action->execute($data);

        $statusCode = $execution->status === 'pending' ? 202 : 201;

        return response()->json(['data' => $execution], $statusCode);
    }

    public function scores(AgentDeployment $deployment, Request $request, SkillQueryService $query): JsonResponse
    {
        $this->authorize('view', $deployment);

        $period = $request->input('period', now()->format('Y-m'));
        $orgId = session('current_organization_id');

        $scores = $query->getDeploymentScores(
            deploymentId: $deployment->id,
            organizationId: $orgId,
            period: $period,
        );

        return response()->json(['data' => $scores]);
    }
}

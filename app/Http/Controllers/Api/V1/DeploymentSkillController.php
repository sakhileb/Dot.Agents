<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Skills\AssignSkillToDeploymentAction;
use App\DTOs\Skills\AssignSkillData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSkillRequest;
use App\Http\Requests\ToggleSkillRequest;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use Illuminate\Http\JsonResponse;

/**
 * Manages which skills are assigned to a deployment and their enabled/disabled state.
 *
 * Catalog browsing → SkillCatalogController
 * Execution and scoring → SkillExecutionController
 */
class DeploymentSkillController extends Controller
{
    public function index(AgentDeployment $deployment): JsonResponse
    {
        $this->authorize('view', $deployment);

        $assignments = $deployment->skills()
            ->active()
            ->with('permissions')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $assignments]);
    }

    public function assign(
        AssignSkillRequest $request,
        AgentDeployment $deployment,
        AssignSkillToDeploymentAction $action
    ): JsonResponse {
        $this->authorize('update', $deployment);

        $orgId = session('current_organization_id');

        $data = AssignSkillData::fromArray([
            'skill_id' => $request->integer('skill_id'),
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $orgId,
            'is_enabled' => $request->boolean('is_enabled', true),
            'config' => $request->input('config', []),
        ]);

        $assignment = $action->execute($data);

        return response()->json(['data' => $assignment], 201);
    }

    public function toggleSkill(
        ToggleSkillRequest $request,
        AgentDeployment $deployment,
        AgentSkill $skill
    ): JsonResponse {
        $this->authorize('update', $deployment);

        $assignment = $deployment->skillAssignments()
            ->where('skill_id', $skill->id)
            ->firstOrFail();

        $assignment->update(['is_enabled' => $request->boolean('is_enabled')]);

        return response()->json(['data' => $assignment]);
    }
}

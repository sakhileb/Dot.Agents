<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Skills\AssignSkillToDeploymentAction;
use App\Actions\Skills\ExecuteSkillAction;
use App\DTOs\Skills\AssignSkillData;
use App\DTOs\Skills\ExecuteSkillData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSkillRequest;
use App\Http\Requests\ExecuteSkillRequest;
use App\Http\Requests\ToggleSkillRequest;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Services\Skills\SkillQueryService;
use App\Support\TaggableCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SkillController extends Controller
{
    // ── Skill catalog ─────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        // Cache skill catalog for 10 minutes; tag-invalidated when skills change.
        $cacheKey = 'skill_catalog:'.md5($request->getQueryString() ?? '');

        $skills = TaggableCache::remember(['skills', 'catalog'], $cacheKey, 600, function () use ($request) {
            return AgentSkill::active()
                ->when($request->filled('department'), fn ($q) => $q->forDepartment($request->department))
                ->when($request->filled('risk_level'), fn ($q) => $q->byRisk($request->risk_level))
                ->when($request->filled('layer'), fn ($q) => $q->forLayer($request->layer))
                ->when($request->filled('approval_required'), fn ($q) => $q->where('approval_required', filter_var($request->approval_required, FILTER_VALIDATE_BOOLEAN)))
                ->orderBy('department')
                ->orderBy('name')
                ->get();
        });

        return response()->json(['data' => $skills]);
    }

    public function show(AgentSkill $skill): JsonResponse
    {
        $this->authorize('view', $skill);

        return response()->json([
            'data' => $skill->load(['permissions', 'requirements']),
        ]);
    }

    // ── Per-deployment skill management ──────────────────

    public function deploymentSkills(AgentDeployment $deployment): JsonResponse
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

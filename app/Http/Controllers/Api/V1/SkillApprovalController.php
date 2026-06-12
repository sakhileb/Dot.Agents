<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Skills\ProcessSkillApprovalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSkillApprovalRequest;
use App\Models\AgentSkillApproval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = session('current_organization_id');

        $approvals = AgentSkillApproval::where('organization_id', $orgId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('risk_level'), fn ($q) => $q->forRisk($request->risk_level))
            ->with(['skill:id,name,department,risk_level', 'requestedBy:id,name', 'deployment:id,name'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($approvals);
    }

    public function show(AgentSkillApproval $approval): JsonResponse
    {
        $this->authorize('view', $approval);

        return response()->json([
            'data' => $approval->load(['skill', 'requestedBy', 'reviewedBy', 'deployment', 'execution']),
        ]);
    }

    public function approve(
        ReviewSkillApprovalRequest $request,
        AgentSkillApproval $approval,
        ProcessSkillApprovalAction $action
    ): JsonResponse {
        $this->authorize('review', $approval);

        $approval = $action->execute(
            approval: $approval,
            decision: 'approved',
            reviewerId: $request->user()->id,
            reviewerNotes: $request->input('reviewer_notes'),
        );

        return response()->json(['data' => $approval]);
    }

    public function reject(
        ReviewSkillApprovalRequest $request,
        AgentSkillApproval $approval,
        ProcessSkillApprovalAction $action
    ): JsonResponse {
        $this->authorize('review', $approval);

        $approval = $action->execute(
            approval: $approval,
            decision: 'rejected',
            reviewerId: $request->user()->id,
            reviewerNotes: $request->input('reviewer_notes'),
        );

        return response()->json(['data' => $approval]);
    }
}

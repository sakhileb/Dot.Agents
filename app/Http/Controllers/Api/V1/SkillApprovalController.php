<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Skills\ProcessSkillApprovalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSkillApprovalRequest;
use App\Models\AgentSkillApproval;
use App\Services\Skills\SkillQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillApprovalController extends Controller
{
    public function index(Request $request, SkillQueryService $query): JsonResponse
    {
        $orgId = session('current_organization_id');

        $approvals = $query->getPendingApprovals(
            organizationId: $orgId,
            status: $request->filled('status') ? $request->input('status') : null,
            riskLevel: $request->filled('risk_level') ? $request->input('risk_level') : null,
            perPage: $request->integer('per_page', 20),
        );

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

<?php

namespace App\Actions\Skills;

use App\Events\SkillExecuted;
use App\Models\AgentSkillApproval;
use App\Models\AgentSkillAudit;
use App\Models\AgentSkillExecution;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ProcessSkillApprovalAction
{
    /**
     * Approve or reject a pending skill execution approval.
     * On approval the linked execution transitions to 'running'.
     */
    public function execute(
        AgentSkillApproval $approval,
        string $decision,           // 'approved' | 'rejected'
        int $reviewerId,
        ?string $reviewerNotes = null
    ): AgentSkillApproval {
        Gate::authorize('review', $approval);

        abort_unless($approval->isPending(), 422, 'This approval has already been processed.');
        abort_unless(in_array($decision, ['approved', 'rejected']), 422, 'Invalid decision.');

        $approval->update([
            'status' => $decision,
            'reviewed_by' => $reviewerId,
            'reviewer_notes' => $reviewerNotes,
            'reviewed_at' => now(),
        ]);

        // Audit the decision
        AgentSkillAudit::create([
            'uuid' => (string) Str::uuid(),
            'skill_id' => $approval->skill_id,
            'execution_id' => $approval->execution_id,
            'agent_deployment_id' => $approval->agent_deployment_id,
            'organization_id' => $approval->organization_id,
            'actor_id' => $reviewerId,
            'event_type' => $decision === 'approved'
                ? AgentSkillAudit::EVENT_APPROVED
                : AgentSkillAudit::EVENT_REJECTED,
            'outcome' => $decision === 'approved'
                ? AgentSkillAudit::OUTCOME_SUCCESS
                : AgentSkillAudit::OUTCOME_BLOCKED,
            'reason' => $reviewerNotes,
            'occurred_at' => now(),
        ]);

        // If approved, advance the execution to running
        if ($decision === 'approved' && $approval->execution_id) {
            $execution = AgentSkillExecution::find($approval->execution_id);
            if ($execution?->status === 'pending') {
                $execution->update([
                    'status' => 'running',
                    'executed_at' => now(),
                ]);
                event(new SkillExecuted($execution));
            }
        }

        // If rejected, mark the execution as skipped
        if ($decision === 'rejected' && $approval->execution_id) {
            AgentSkillExecution::where('id', $approval->execution_id)
                ->where('status', 'pending')
                ->update(['status' => 'skipped', 'error' => "Approval rejected: {$reviewerNotes}"]);
        }

        return $approval->fresh();
    }
}

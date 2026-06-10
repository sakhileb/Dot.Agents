<?php

namespace App\Actions\Governance;

use App\Events\ApprovalProcessed;
use App\Models\AgentApproval;
use App\Models\DecisionLog;
use App\Services\Governance\AuditService;
use App\Services\Governance\PredictionAccuracyTrackingService;
use Illuminate\Support\Facades\Gate;

class ProcessApprovalAction
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly PredictionAccuracyTrackingService $predictionAccuracy,
    ) {}

    public function execute(AgentApproval $approval, string $decision, ?string $notes = null): AgentApproval
    {
        Gate::authorize('review', $approval);

        if ($approval->status !== 'pending') {
            throw new \RuntimeException("Approval #{$approval->id} is already {$approval->status}.");
        }

        if ($approval->isExpired()) {
            throw new \RuntimeException("Approval #{$approval->id} has expired.");
        }

        $approval->update([
            'status' => $decision,
            'reviewer_notes' => $notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($approval->task_id) {
            $newTaskStatus = match ($decision) {
                'approved' => 'in_progress',
                'rejected' => 'failed',
                'escalated' => 'pending_escalation',
                default => 'pending',
            };
            $approval->task?->update(['status' => $newTaskStatus]);

            // Record the outcome on the linked DecisionLog so prediction accuracy
            // and data-trust dimensions receive real data.
            $this->recordDecisionOutcome($approval->task_id, $decision);
        }

        $this->auditService->logUserAction(
            event: "approval.{$decision}",
            description: "Approval #{$approval->id} {$decision} by reviewer",
            subject: $approval,
            metadata: ['notes' => $notes],
        );

        event(new ApprovalProcessed($approval));

        return $approval->refresh();
    }

    private function recordDecisionOutcome(int $taskId, string $approvalDecision): void
    {
        $finalOutcome = match ($approvalDecision) {
            'approved' => 'implemented',
            'rejected' => 'rejected',
            'escalated' => 'escalated',
            default => null,
        };

        if ($finalOutcome === null) {
            return;
        }

        $decisionLog = DecisionLog::where('task_id', $taskId)
            ->orderByDesc('created_at')
            ->first();

        if ($decisionLog && $decisionLog->final_outcome === null) {
            $decisionLog->update([
                'final_outcome' => $finalOutcome,
                'human_verdict' => $approvalDecision === 'approved' ? 'correct' : 'incorrect',
            ]);

            $this->predictionAccuracy->recordOutcome($decisionLog);
        }
    }
}

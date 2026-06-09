<?php

namespace App\Actions\Governance;

use App\Events\ApprovalProcessed;
use App\Models\AgentApproval;
use App\Services\Governance\AuditService;
use Illuminate\Support\Facades\Gate;

class ProcessApprovalAction
{
    public function __construct(private readonly AuditService $auditService) {}

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
}

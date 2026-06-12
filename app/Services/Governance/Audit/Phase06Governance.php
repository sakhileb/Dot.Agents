<?php

namespace App\Services\Governance\Audit;

use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Services\Governance\Audit\Contracts\DWCAPhaseContract;

/**
 * Phase 06 — Governance
 *
 * Checks that the deployment has audit logs covering its completed tasks,
 * no open critical security events, a configured confidence threshold,
 * a deployment mode, and a sensible approval workflow threshold.
 */
class Phase06Governance implements DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array
    {
        $recentAuditLogs = AuditLog::where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $recentTasks = AgentTask::where('agent_deployment_id', $deployment->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->count();

        $openCriticalEvents = SecurityEvent::where('agent_deployment_id', $deployment->id)
            ->where('status', 'open')
            ->where('severity', 'critical')
            ->count();

        $checks = [
            'audit_logs_present' => $recentAuditLogs > 0 || $recentTasks === 0,
            'no_open_critical_security_events' => $openCriticalEvents === 0,
            'deployment_has_confidence_threshold' => $deployment->confidence_threshold > 0,
            'deployment_has_mode_configured' => ! empty($deployment->deployment_mode),
            'approval_workflow_configured' => $deployment->confidence_threshold <= 90,
        ];

        $passed = array_sum(array_map(fn ($v) => (int) $v, $checks));
        $score = (int) round(($passed / count($checks)) * 100);

        return [
            'phase' => 'Governance',
            'score' => $score,
            'passed' => $score >= 80,
            'checks' => $checks,
            'failures' => array_keys(array_filter($checks, fn ($v) => ! $v)),
        ];
    }
}

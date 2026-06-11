<?php

namespace App\Listeners;

use App\Events\AgentDeployed;
use App\Jobs\GenerateAgentScorecard;
use App\Models\AgentScorecard;
use App\Models\AgentSkill;
use App\Models\AgentSkillAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SCCS Auto-Setup Listener
 *
 * Triggered on every AgentDeployed event.
 *
 * For SCCS agents (Social Commerce & Customer Success):
 *   1. Auto-assigns all matching sccs.* skills to the deployment
 *   2. Dispatches scorecard generation for the new deployment
 *
 * SCCS agent slugs:
 *   - social-media-manager-agent
 *   - lead-generation-social-agent
 *   - social-customer-support-agent
 *   - sales-conversion-agent
 *   - brand-reputation-monitor-agent
 */
class ProvisionSCCSSkillsAndScorecard implements ShouldQueue
{
    public string $queue = 'agent-tasks';

    public int $tries = 3;

    private const SCCS_AGENT_SLUGS = [
        'social-media-manager-agent',
        'lead-generation-social-agent',
        'social-customer-support-agent',
        'sales-conversion-agent',
        'brand-reputation-monitor-agent',
    ];

    public function handle(AgentDeployed $event): void
    {
        $deployment = $event->deployment;
        $agent = $deployment->agent;

        if (! $agent) {
            return;
        }

        // Only provision for SCCS agents
        if (! in_array($agent->slug, self::SCCS_AGENT_SLUGS, true)) {
            return;
        }

        Log::info('[ProvisionSCCSSkillsAndScorecard] Provisioning SCCS skills for deployment', [
            'deployment_id' => $deployment->id,
            'agent_slug' => $agent->slug,
            'organization_id' => $deployment->organization_id,
        ]);

        // Assign all active sccs.* skills to this deployment
        $sccsSkills = AgentSkill::where('key', 'like', 'sccs.%')
            ->where('is_active', true)
            ->get();

        foreach ($sccsSkills as $skill) {
            try {
                // Direct model operation — system-level listener without user context.
                // Authorization was established when the AgentDeployed event was fired.
                AgentSkillAssignment::updateOrCreate(
                    [
                        'agent_deployment_id' => $deployment->id,
                        'skill_id' => $skill->id,
                    ],
                    [
                        'organization_id' => $deployment->organization_id,
                        'is_enabled' => true,
                    ]
                );
            } catch (Throwable $e) {
                Log::warning('[ProvisionSCCSSkillsAndScorecard] Failed to assign skill', [
                    'skill_id' => $skill->id,
                    'skill_key' => $skill->key,
                    'deployment_id' => $deployment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Dispatch scorecard generation for this new deployment (only if not already generated this month)
        if (class_exists(GenerateAgentScorecard::class)) {
            $periodStart = now()->startOfMonth()->toDateTimeString();
            $scorecardExists = AgentScorecard::where('agent_deployment_id', $deployment->id)
                ->where('period', 'monthly')
                ->where('period_start', $periodStart)
                ->exists();

            if (! $scorecardExists) {
                GenerateAgentScorecard::dispatch($deployment)->onQueue('agent-tasks');
            }
        }

        Log::info('[ProvisionSCCSSkillsAndScorecard] SCCS setup complete', [
            'deployment_id' => $deployment->id,
            'skills_assigned' => $sccsSkills->count(),
        ]);
    }
}

<?php

namespace App\Services\Governance;

use App\Models\Organization;
use App\Models\OrganizationDNA;
use Illuminate\Support\Facades\Cache;

/**
 * Enterprise Constitution Service
 *
 * Manages the organizational DNA that every agent inherits automatically.
 * Injects constitutional principles into agent system prompts so all agents
 * operate within the organization's mission, values, and risk appetite.
 *
 * Core principle: The Enterprise Constitution governs ALL agent behavior.
 * No agent acts outside the organization's defined boundaries.
 */
class EnterpriseConstitutionService
{
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Get or create organizational DNA for an organization.
     */
    public function getDNA(int $organizationId): ?OrganizationDNA
    {
        return Cache::remember(
            "org_dna_{$organizationId}",
            self::CACHE_TTL,
            fn () => OrganizationDNA::where('organization_id', $organizationId)->first()
        );
    }

    /**
     * Upsert organizational DNA from configuration.
     */
    public function setDNA(int $organizationId, array $config): OrganizationDNA
    {
        Cache::forget("org_dna_{$organizationId}");

        return OrganizationDNA::updateOrCreate(
            ['organization_id' => $organizationId],
            $config
        );
    }

    /**
     * Build the constitutional context string to inject into agent system prompts.
     *
     * Returns null if no DNA configured (agent operates without explicit constitution).
     */
    public function buildConstitutionalContext(int $organizationId): ?string
    {
        $dna = $this->getDNA($organizationId);

        if (! $dna) {
            return null;
        }

        return $dna->toConstitutionalContext();
    }

    /**
     * Validate whether a proposed agent action aligns with the organization's constitution.
     *
     * Returns [aligned: bool, violations: array, risk_level: string]
     */
    public function validateAlignment(int $organizationId, string $action, array $context = []): array
    {
        $dna = $this->getDNA($organizationId);

        if (! $dna) {
            return ['aligned' => true, 'violations' => [], 'risk_level' => 'low'];
        }

        $violations = [];
        $riskLevel = 'low';

        // Risk appetite check
        if ($dna->risk_appetite === 'conservative' && ($context['risk_score'] ?? 0) > 30) {
            $violations[] = 'Action risk score exceeds conservative risk appetite threshold (30)';
            $riskLevel = 'high';
        }

        // Financial budget check
        if ($dna->monthly_ai_budget_usd && ($context['estimated_cost'] ?? 0) > ($dna->monthly_ai_budget_usd * 0.1)) {
            $violations[] = 'Action estimated cost exceeds 10% of monthly AI budget';
            $riskLevel = 'medium';
        }

        // Compliance constraints
        $complianceRequirements = $dna->compliance_requirements ?? [];
        if (in_array('GDPR', $complianceRequirements) && ($context['processes_pii'] ?? false)) {
            if (! ($context['gdpr_compliant'] ?? false)) {
                $violations[] = 'GDPR compliance required: PII processing must be explicitly authorized';
                $riskLevel = 'critical';
            }
        }

        return [
            'aligned' => empty($violations),
            'violations' => $violations,
            'risk_level' => $riskLevel,
        ];
    }

    /**
     * Get the risk appetite level as a numeric score (for threshold comparisons).
     * conservative=25, moderate=50, aggressive=75, calculated=60
     */
    public function getRiskAppetiteScore(int $organizationId): int
    {
        $dna = $this->getDNA($organizationId);

        return match ($dna?->risk_appetite) {
            'conservative' => 25,
            'moderate' => 50,
            'calculated' => 60,
            'aggressive' => 75,
            default => 50,
        };
    }

    /**
     * Initialize sensible defaults for a new organization.
     */
    public function initializeDefaults(Organization $organization): OrganizationDNA
    {
        return $this->setDNA($organization->id, [
            'mission' => "Leverage AI to drive operational excellence and sustainable growth for {$organization->name}.",
            'vision' => 'Become a fully AI-augmented enterprise where every team member is empowered by intelligent agents.',
            'values' => ['Integrity', 'Customer Obsession', 'Continuous Improvement', 'Data-Driven Decisions'],
            'leadership_principles' => [
                'Safety Before Speed',
                'Customer Obsession',
                'Data-Driven Decisions',
                'Long-Term Thinking',
            ],
            'decision_principles' => [
                'Always verify before acting on unconfirmed data',
                'Escalate when confidence is below threshold',
                'Document reasoning for every significant decision',
                'Protect customer data above operational convenience',
            ],
            'risk_appetite' => 'moderate',
            'compliance_requirements' => [],
            'strategic_priorities' => ['Operational Efficiency', 'Customer Satisfaction', 'Revenue Growth'],
        ]);
    }
}

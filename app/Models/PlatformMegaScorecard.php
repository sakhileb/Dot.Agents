<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformMegaScorecard extends Model
{
    use HasFactory, HasOrganizationScope, MassPrunable;

    protected $fillable = [
        'organization_id', 'final_score', 'certification', 'level', 'gate_pass',

        // Technical
        'security_score', 'compliance_score', 'architecture_score', 'infrastructure_score',
        'data_engineering_score', 'performance_score', 'api_score', 'testing_score',
        'observability_score', 'communication_score',

        // Intelligence
        'ai_governance_score', 'ai_accuracy_score', 'ai_drift_score', 'agent_reliability_score',
        'agent_collaboration_score', 'reality_alignment_score', 'hallucination_resistance_score',
        'decision_intelligence_score',

        // Business
        'customer_success_score', 'operational_efficiency_score', 'financial_intelligence_score',
        'product_strategy_score', 'innovation_score',

        // Source
        'data_trust_score', 'prediction_accuracy_score', 'org_memory_score',

        // Meta
        'gate_details', 'full_breakdown', 'recommendations',
    ];

    protected $casts = [
        'gate_pass' => 'boolean',
        'gate_details' => 'array',
        'full_breakdown' => 'array',
        'recommendations' => 'array',
        'final_score' => 'decimal:2',
        'security_score' => 'decimal:2',
        'compliance_score' => 'decimal:2',
        'architecture_score' => 'decimal:2',
        'infrastructure_score' => 'decimal:2',
        'data_engineering_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'api_score' => 'decimal:2',
        'testing_score' => 'decimal:2',
        'observability_score' => 'decimal:2',
        'communication_score' => 'decimal:2',
        'ai_governance_score' => 'decimal:2',
        'ai_accuracy_score' => 'decimal:2',
        'ai_drift_score' => 'decimal:2',
        'agent_reliability_score' => 'decimal:2',
        'agent_collaboration_score' => 'decimal:2',
        'reality_alignment_score' => 'decimal:2',
        'hallucination_resistance_score' => 'decimal:2',
        'decision_intelligence_score' => 'decimal:2',
        'customer_success_score' => 'decimal:2',
        'operational_efficiency_score' => 'decimal:2',
        'financial_intelligence_score' => 'decimal:2',
        'product_strategy_score' => 'decimal:2',
        'innovation_score' => 'decimal:2',
        'data_trust_score' => 'decimal:2',
        'prediction_accuracy_score' => 'decimal:2',
        'org_memory_score' => 'decimal:2',
    ];

    /**
     * Prune scorecards older than 12 months.
     */
    public function prunable(): Builder
    {
        return static::withoutGlobalScope('organization')
            ->where('created_at', '<=', now()->subMonths(12));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getCertificationColorAttribute(): string
    {
        return match (true) {
            str_contains($this->certification, 'SELF-GOVERNING') => 'emerald',
            str_contains($this->certification, 'AUTONOMOUS') => 'green',
            str_contains($this->certification, 'ENTERPRISE PRODUCTION') => 'blue',
            str_contains($this->certification, 'ENTERPRISE READY') => 'sky',
            str_contains($this->certification, 'CONDITIONAL') => 'yellow',
            default => 'red',
        };
    }
}

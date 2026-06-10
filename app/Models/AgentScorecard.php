<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentScorecard extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'agent_deployment_id', 'organization_id', 'period', 'period_start', 'period_end',
        'accuracy_score', 'productivity_score', 'compliance_score', 'reliability_score',
        'trustworthiness_score', 'cost_savings_score', 'revenue_impact_score',
        'risk_impact_score', 'user_satisfaction_score', 'learning_rate_score',
        'overall_health_score', 'tasks_completed', 'tasks_failed', 'decisions_made',
        'decisions_overridden', 'hallucinations_detected', 'approvals_requested',
        'approvals_granted', 'total_cost', 'estimated_savings', 'estimated_revenue_impact',
        'total_tokens_used', 'avg_response_time_ms', 'uptime_percentage',
        'detailed_metrics', 'recommendations',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'accuracy_score' => 'decimal:2',
        'productivity_score' => 'decimal:2',
        'compliance_score' => 'decimal:2',
        'reliability_score' => 'decimal:2',
        'trustworthiness_score' => 'decimal:2',
        'cost_savings_score' => 'decimal:2',
        'revenue_impact_score' => 'decimal:2',
        'risk_impact_score' => 'decimal:2',
        'user_satisfaction_score' => 'decimal:2',
        'learning_rate_score' => 'decimal:2',
        'overall_health_score' => 'decimal:2',
        'total_cost' => 'decimal:4',
        'estimated_savings' => 'decimal:2',
        'estimated_revenue_impact' => 'decimal:2',
        'avg_response_time_ms' => 'decimal:2',
        'uptime_percentage' => 'decimal:2',
        'detailed_metrics' => 'array',
        'recommendations' => 'array',
    ];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getHealthStatusAttribute(): string
    {
        return match (true) {
            $this->overall_health_score >= 90 => 'excellent',
            $this->overall_health_score >= 75 => 'good',
            $this->overall_health_score >= 60 => 'fair',
            $this->overall_health_score >= 40 => 'poor',
            default => 'critical',
        };
    }

    public function getHealthColorAttribute(): string
    {
        return match ($this->health_status) {
            'excellent' => 'emerald',
            'good' => 'green',
            'fair' => 'yellow',
            'poor' => 'orange',
            default => 'red',
        };
    }

    public function calculateOverallScore(): float
    {
        $weights = [
            'accuracy_score' => 0.15,
            'productivity_score' => 0.12,
            'compliance_score' => 0.15,
            'reliability_score' => 0.12,
            'trustworthiness_score' => 0.15,
            'cost_savings_score' => 0.08,
            'revenue_impact_score' => 0.08,
            'risk_impact_score' => 0.08,
            'user_satisfaction_score' => 0.05,
            'learning_rate_score' => 0.02,
        ];

        $total = 0;
        foreach ($weights as $field => $weight) {
            $total += (float) $this->{$field} * $weight;
        }

        return round($total, 2);
    }
}

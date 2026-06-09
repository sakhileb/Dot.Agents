<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DecisionLog extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'agent_deployment_id', 'organization_id', 'task_id', 'session_id',
        'decision_type', 'title', 'decision_summary', 'reasoning', 'evidence_used',
        'alternatives_considered', 'proposed_actions', 'confidence_score', 'risk_score',
        'impact_score', 'delusion_risk_score', 'reality_alignment_score',
        'verification_score', 'evidence_quality_score', 'source_credibility_score',
        'assumption_count', 'contradicting_evidence', 'delusion_analysis',
        'compliance_checked', 'compliance_passed', 'compliance_notes',
        'requires_human_review', 'human_reviewed', 'reviewed_by', 'human_verdict',
        'human_feedback', 'reviewed_at', 'final_outcome', 'outcome_notes', 'metadata',
    ];

    protected $casts = [
        'evidence_used' => 'array',
        'alternatives_considered' => 'array',
        'proposed_actions' => 'array',
        'compliance_notes' => 'array',
        'metadata' => 'array',
        'delusion_analysis' => 'array',
        'confidence_score' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'impact_score' => 'decimal:2',
        'delusion_risk_score' => 'decimal:2',
        'reality_alignment_score' => 'decimal:2',
        'verification_score' => 'decimal:2',
        'evidence_quality_score' => 'decimal:2',
        'source_credibility_score' => 'decimal:2',
        'contradicting_evidence' => 'boolean',
        'compliance_checked' => 'boolean',
        'compliance_passed' => 'boolean',
        'requires_human_review' => 'boolean',
        'human_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isDelusionRisk(): bool
    {
        return $this->delusion_risk_score >= 60;
    }

    public function getDelusionRiskLevelAttribute(): string
    {
        return match (true) {
            $this->delusion_risk_score >= 80 => 'critical',
            $this->delusion_risk_score >= 60 => 'high',
            $this->delusion_risk_score >= 40 => 'medium',
            default => 'low',
        };
    }

    public function getRiskLevelAttribute(): string
    {
        return match (true) {
            $this->risk_score >= 80 => 'critical',
            $this->risk_score >= 60 => 'high',
            $this->risk_score >= 40 => 'medium',
            default => 'low',
        };
    }
}

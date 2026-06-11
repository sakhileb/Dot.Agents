<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Enterprise Decision
 *
 * Permanent, immutable record of every major organizational decision.
 * Includes full reasoning chain, evidence, alternatives, and expected outcomes.
 * Used by the Enterprise Brain for decision recall and learning.
 */
class EnterpriseDecision extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'triggered_by', 'council_session_id',
        'decision_category', 'title', 'context', 'reasoning_chain', 'evidence',
        'alternatives_considered', 'expected_outcomes', 'confidence_score',
        'risk_score', 'financial_impact_usd', 'time_horizon', 'status',
        'actual_outcomes', 'outcome_accuracy',
    ];

    protected $casts = [
        'reasoning_chain' => 'array',
        'evidence' => 'array',
        'alternatives_considered' => 'array',
        'expected_outcomes' => 'array',
        'actual_outcomes' => 'array',
        'confidence_score' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'financial_impact_usd' => 'decimal:2',
        'outcome_accuracy' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function councilSession(): BelongsTo
    {
        return $this->belongsTo(ExecutiveCouncilSession::class, 'council_session_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Executive Recommendation
 *
 * A single executive agent's domain assessment and vote within a council session.
 * CEO provides strategic view, CFO financial view, CISO security view, etc.
 */
class ExecutiveRecommendation extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'session_id', 'organization_id', 'agent_role', 'agent_deployment_id_ref',
        'domain', 'recommendation', 'impact_analysis', 'confidence_score',
        'risk_score', 'evidence', 'alternatives', 'vote', 'vote_rationale',
    ];

    protected $casts = [
        'impact_analysis' => 'array',
        'evidence' => 'array',
        'alternatives' => 'array',
        'confidence_score' => 'decimal:2',
        'risk_score' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExecutiveCouncilSession::class, 'session_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

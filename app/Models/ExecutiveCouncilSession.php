<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Executive Council Session
 *
 * Records a multi-agent deliberation session where AI executives
 * (CEO, CFO, CTO, etc.) collaboratively reason about major decisions.
 */
class ExecutiveCouncilSession extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'triggered_by', 'session_type', 'title',
        'context', 'status', 'input_data', 'constraints', 'financial_threshold',
        'consensus_recommendation', 'consensus_confidence', 'final_decision',
        'rationale', 'agents_consulted', 'votes_cast', 'votes_for', 'votes_against',
        'deliberation_started_at', 'completed_at', 'deliberation_duration_seconds',
    ];

    protected $casts = [
        'input_data' => 'array',
        'constraints' => 'array',
        'consensus_recommendation' => 'array',
        'consensus_confidence' => 'decimal:2',
        'financial_threshold' => 'decimal:2',
        'deliberation_started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function recommendations(): HasMany
    {
        return $this->hasMany(ExecutiveRecommendation::class, 'session_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

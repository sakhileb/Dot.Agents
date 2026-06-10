<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSkillScore extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'skill_id',
        'agent_deployment_id',
        'organization_id',
        'period',
        'total_executions',
        'successful_executions',
        'failed_executions',
        'blocked_executions',
        'approval_requests',
        'approvals_granted',
        'approvals_rejected',
        'accuracy_score',
        'reliability_score',
        'compliance_score',
        'avg_confidence',
        'avg_duration_ms',
        'success_rate',
    ];

    protected $casts = [
        'accuracy_score' => 'decimal:2',
        'reliability_score' => 'decimal:2',
        'compliance_score' => 'decimal:2',
        'avg_confidence' => 'decimal:2',
        'avg_duration_ms' => 'decimal:2',
        'success_rate' => 'decimal:2',
    ];

    // ── Scopes ──────────────────────────────────────────

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->where('period', now()->format('Y-m'));
    }

    // ── Relationships ────────────────────────────────────

    public function skill(): BelongsTo
    {
        return $this->belongsTo(AgentSkill::class, 'skill_id');
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // ── Computed helpers ─────────────────────────────────

    /** Overall skill health score (0-100). */
    public function healthScore(): float
    {
        $scores = array_filter([
            $this->accuracy_score,
            $this->reliability_score,
            $this->compliance_score,
        ]);

        return count($scores) > 0
            ? round(array_sum($scores) / count($scores), 2)
            : 0.0;
    }
}

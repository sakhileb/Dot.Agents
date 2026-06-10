<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentSkillApproval extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid',
        'skill_id',
        'execution_id',
        'agent_deployment_id',
        'organization_id',
        'requested_by',
        'reviewed_by',
        'status',
        'risk_level',
        'context',
        'justification',
        'reviewer_notes',
        'expires_at',
        'reviewed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $approval) {
            if (empty($approval->uuid)) {
                $approval->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForRisk($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    // ── Relationships ────────────────────────────────────

    public function skill(): BelongsTo
    {
        return $this->belongsTo(AgentSkill::class, 'skill_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AgentSkillExecution::class, 'execution_id');
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Helpers ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() && $this->status === self::STATUS_PENDING;
    }
}

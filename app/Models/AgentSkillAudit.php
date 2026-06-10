<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentSkillAudit extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid',
        'skill_id',
        'execution_id',
        'agent_deployment_id',
        'organization_id',
        'actor_id',
        'event_type',
        'outcome',
        'policy_checks',
        'input_hash',
        'metadata',
        'reason',
        'confidence_at_execution',
        'occurred_at',
    ];

    protected $casts = [
        'policy_checks' => 'array',
        'input_hash' => 'array',
        'metadata' => 'array',
        'confidence_at_execution' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    // Event types
    public const EVENT_EXECUTED = 'executed';

    public const EVENT_BLOCKED = 'blocked';

    public const EVENT_APPROVED = 'approved';

    public const EVENT_REJECTED = 'rejected';

    public const EVENT_DELEGATED = 'delegated';

    public const EVENT_FAILED = 'failed';

    public const EVENT_SKIPPED = 'skipped';

    public const EVENT_APPROVAL_REQUIRED = 'approval_required';

    // Outcomes
    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILURE = 'failure';

    public const OUTCOME_BLOCKED = 'blocked';

    public const OUTCOME_PENDING_APPROVAL = 'pending_approval';

    protected static function boot(): void
    {
        parent::boot();

        // Immutable — no updates or deletes
        static::updating(fn () => false);
        static::deleting(fn () => false);

        static::creating(function (self $audit) {
            if (empty($audit->uuid)) {
                $audit->uuid = (string) Str::uuid();
            }
            if (empty($audit->occurred_at)) {
                $audit->occurred_at = now();
            }
        });
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBlocked($query)
    {
        return $query->where('event_type', self::EVENT_BLOCKED);
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

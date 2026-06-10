<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgentTask extends Model
{
    use HasFactory, HasOrganizationScope, MassPrunable;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'agent_deployment_id', 'organization_id', 'session_id',
        'assigned_by', 'parent_task_id', 'title', 'description', 'task_type',
        'priority', 'status', 'input_data', 'output_data', 'result_summary',
        'artifacts', 'confidence_score', 'accuracy_score', 'risk_score',
        'delusion_risk_score', 'reality_alignment_score',
        'estimated_duration_minutes', 'actual_duration_minutes',
        'token_count', 'cost', 'due_at', 'started_at', 'completed_at', 'metadata',
        'user_rating', 'user_feedback', 'rated_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'artifacts' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'delusion_risk_score' => 'decimal:2',
        'reality_alignment_score' => 'decimal:2',
        'cost' => 'decimal:4',
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rated_at' => 'datetime',
        'user_rating' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $task) {
            if (empty($task->uuid)) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Prune completed/failed tasks older than 90 days.
     * Bypasses the organization global scope to prune across all tenants.
     */
    public function prunable(): Builder
    {
        return static::withoutGlobalScope('organization')
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->where('created_at', '<=', now()->subDays(90));
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AgentSession::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    public function approval()
    {
        return $this->hasOne(AgentApproval::class, 'task_id');
    }

    public function decisionLog()
    {
        return $this->hasOne(DecisionLog::class, 'task_id');
    }

    public function isHighRisk(): bool
    {
        return ($this->risk_score ?? 0) >= 70 || ($this->delusion_risk_score ?? 0) >= 60;
    }

    public function getDurationAttribute(): ?string
    {
        if ($this->actual_duration_minutes) {
            return $this->actual_duration_minutes < 60
                ? $this->actual_duration_minutes.'m'
                : round($this->actual_duration_minutes / 60, 1).'h';
        }

        return null;
    }
}

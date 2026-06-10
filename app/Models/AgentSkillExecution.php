<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentSkillExecution extends Model
{
    use HasFactory, HasOrganizationScope, MassPrunable;

    protected $fillable = [
        'uuid',
        'skill_id',
        'agent_deployment_id',
        'organization_id',
        'task_id',
        'trigger',
        'status',
        'input',
        'output',
        'findings',
        'confidence',
        'duration_ms',
        'error',
        'executed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'findings' => 'array',
        'confidence' => 'decimal:2',
        'executed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $exec) {
            if (empty($exec->uuid)) {
                $exec->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Prune completed/failed skill executions older than 60 days.
     * Bypasses the organization global scope to prune across all tenants.
     */
    public function prunable(): Builder
    {
        return static::withoutGlobalScope('organization')
            ->whereIn('status', ['completed', 'failed', 'skipped'])
            ->where('created_at', '<=', now()->subDays(60));
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }

    // ── Helpers ──────────────────────────────────────────

    public function passed(): bool
    {
        return $this->status === 'completed';
    }

    public function hasCriticalFindings(): bool
    {
        return ! empty($this->findings);
    }
}

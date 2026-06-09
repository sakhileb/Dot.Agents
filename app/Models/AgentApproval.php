<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentApproval extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'task_id', 'agent_deployment_id', 'organization_id', 'requested_from',
        'approval_type', 'title', 'description', 'proposed_action', 'impact_assessment',
        'risk_level', 'confidence_score', 'status', 'reviewed_by', 'reviewer_notes',
        'reviewer_data', 'reviewed_at', 'expires_at',
    ];

    protected $casts = [
        'proposed_action' => 'array',
        'impact_assessment' => 'array',
        'reviewer_data' => 'array',
        'confidence_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $approval) {
            if (empty($approval->uuid)) {
                $approval->uuid = (string) Str::uuid();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function requestedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_from');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialCampaign extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'agent_deployment_id',
        'name', 'description', 'campaign_type', 'status',
        'target_platforms', 'target_audience', 'goals',
        'budget', 'spent', 'starts_at', 'ends_at',
        'metrics', 'ai_strategy',
    ];

    protected $casts = [
        'target_platforms' => 'array',
        'target_audience' => 'array',
        'goals' => 'array',
        'metrics' => 'array',
        'ai_strategy' => 'array',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'campaign_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(SocialConversion::class, 'campaign_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function remainingBudget(): float
    {
        return (float) $this->budget - (float) $this->spent;
    }
}

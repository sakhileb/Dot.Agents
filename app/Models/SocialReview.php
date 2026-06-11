<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialReview extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_account_id', 'agent_deployment_id',
        'platform', 'platform_review_id', 'reviewer_name', 'reviewer_id',
        'rating', 'review_text', 'sentiment', 'sentiment_score',
        'has_response', 'response_text', 'response_is_ai_generated',
        'responded_by', 'responded_at', 'requires_escalation',
        'is_verified_purchase', 'tags', 'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'sentiment_score' => 'decimal:2',
        'has_response' => 'boolean',
        'response_is_ai_generated' => 'boolean',
        'requires_escalation' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'tags' => 'array',
        'responded_at' => 'datetime',
        'reviewed_at' => 'datetime',
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

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isNegative(): bool
    {
        return $this->sentiment === 'negative' || ($this->rating !== null && $this->rating <= 2.0);
    }

    public function isPositive(): bool
    {
        return $this->sentiment === 'positive' || ($this->rating !== null && $this->rating >= 4.0);
    }

    public function needsResponse(): bool
    {
        return ! $this->has_response && ($this->isNegative() || $this->requires_escalation);
    }
}

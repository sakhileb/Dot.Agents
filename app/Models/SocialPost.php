<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialPost extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_page_id', 'agent_deployment_id', 'campaign_id',
        'platform_post_id', 'post_type', 'content', 'media_urls', 'hashtags', 'mentions',
        'link_url', 'status', 'approval_status', 'approved_by', 'approved_at',
        'scheduled_at', 'published_at',
        'like_count', 'comment_count', 'share_count', 'view_count', 'click_count',
        'engagement_rate', 'ai_metadata', 'platform_response',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'ai_metadata' => 'array',
        'platform_response' => 'array',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'engagement_rate' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function socialPage(): BelongsTo
    {
        return $this->belongsTo(SocialPage::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SocialCampaign::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(SocialEngagement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function totalEngagement(): int
    {
        return $this->like_count + $this->comment_count + $this->share_count + $this->click_count;
    }
}

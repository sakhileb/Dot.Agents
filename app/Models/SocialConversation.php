<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialConversation extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_account_id', 'social_page_id',
        'agent_deployment_id', 'assigned_user_id',
        'platform_conversation_id', 'platform', 'channel_type',
        'contact_platform_id', 'contact_name', 'contact_handle', 'contact_avatar',
        'status', 'priority', 'sentiment', 'sentiment_score',
        'intent', 'intent_score', 'requires_human', 'is_lead', 'is_escalated',
        'escalated_to', 'escalated_at', 'first_response_at', 'last_message_at',
        'resolved_at', 'message_count', 'response_time_seconds', 'tags', 'metadata',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:2',
        'intent_score' => 'decimal:2',
        'requires_human' => 'boolean',
        'is_lead' => 'boolean',
        'is_escalated' => 'boolean',
        'message_count' => 'integer',
        'response_time_seconds' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'escalated_at' => 'datetime',
        'first_response_at' => 'datetime',
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
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

    public function socialPage(): BelongsTo
    {
        return $this->belongsTo(SocialPage::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SocialMessage::class);
    }

    public function lead(): HasOne
    {
        return $this->hasOne(SocialLead::class);
    }

    public function sentimentScores(): HasMany
    {
        return $this->hasMany(SocialSentimentScore::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(SocialConversion::class);
    }

    public function isHighIntent(): bool
    {
        return $this->intent_score >= 75;
    }

    public function isNegativeSentiment(): bool
    {
        return in_array($this->sentiment, ['frustrated', 'angry']);
    }

    public function requiresEscalation(): bool
    {
        return $this->requires_human || $this->isNegativeSentiment() || $this->is_escalated;
    }
}

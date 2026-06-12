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

class SocialAccount extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'agent_deployment_id',
        'platform', 'platform_account_id', 'account_name', 'account_handle',
        'account_type', 'avatar_url', 'access_token', 'refresh_token',
        'token_expires_at', 'scopes', 'settings', 'status', 'is_primary',
        'connected_at', 'last_synced_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'scopes' => 'array',
        'settings' => 'array',
        'is_primary' => 'boolean',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

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

    public function pages(): HasMany
    {
        return $this->hasMany(SocialPage::class);
    }

    public function connectionSettings(): HasOne
    {
        return $this->hasOne(SocialConnectionSettings::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SocialConversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SocialReview::class);
    }

    public function sentimentScores(): HasMany
    {
        return $this->hasMany(SocialSentimentScore::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

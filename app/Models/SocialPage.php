<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialPage extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_account_id',
        'platform_page_id', 'name', 'handle', 'category', 'about',
        'avatar_url', 'cover_url', 'website',
        'follower_count', 'following_count', 'engagement_rate',
        'metrics', 'is_verified', 'is_active', 'last_synced_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'follower_count' => 'integer',
        'following_count' => 'integer',
        'engagement_rate' => 'decimal:2',
        'last_synced_at' => 'datetime',
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

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SocialConversation::class);
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(SocialEngagement::class);
    }

    public function getPlatformAttribute(): string
    {
        return $this->socialAccount->platform;
    }
}

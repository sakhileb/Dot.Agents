<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlatformNotification extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'user_id', 'type', 'channel', 'title', 'body',
        'data', 'action_url', 'priority', 'read_at', 'acted_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $n) {
            if (empty($n->uuid)) {
                $n->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}

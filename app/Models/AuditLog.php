<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory, HasOrganizationScope;

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'organization_id', 'auditable_type', 'auditable_id',
        'user_id', 'agent_deployment_id', 'event', 'event_category',
        'description', 'old_values', 'new_values', 'metadata',
        'ip_address', 'user_agent', 'session_id', 'request_id',
        'risk_level', 'flagged', 'flag_reason', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'flagged' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public static function record(
        string $event,
        string $category,
        string $description,
        array $context = []
    ): self {
        return static::create(array_merge([
            'event' => $event,
            'event_category' => $category,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID') ?? (string) Str::uuid(),
        ], $context));
    }

    public function getRiskBadgeColorAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            default => 'green',
        };
    }
}

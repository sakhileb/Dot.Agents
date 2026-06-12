<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory, HasOrganizationScope, MassPrunable;

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

        // ── Immutability enforcement ──────────────────────────────────────────
        // Audit logs are append-only governance records. Preventing updates and
        // deletes ensures a tamper-evident audit trail (SOC2 CC7, ISO27001 A.12.4).
        static::updating(function () {
            throw new \RuntimeException('AuditLog records are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('AuditLog records are immutable and cannot be deleted. Use model:prune for scheduled retention.');
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Define the prunable query — keep 2 years of audit logs by default.
     * Override AUDIT_LOG_RETENTION_DAYS env var to adjust per deployment.
     * Default: 730 days (2 years) to satisfy typical compliance requirements.
     */
    public function prunable(): Builder
    {
        $days = (int) config('audit.retention_days', 730);

        return static::withoutGlobalScope('organization')
            ->where('created_at', '<', now()->subDays($days));
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
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Correlation-ID')
                ?? request()->header('X-Request-ID')
                ?? (string) Str::uuid(),
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

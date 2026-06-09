<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SecurityEvent extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'agent_deployment_id', 'user_id', 'event_type',
        'severity', 'title', 'description', 'event_data', 'indicators', 'source_ip',
        'status', 'action_taken', 'auto_remediated', 'remediation_notes',
        'assigned_to', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'indicators' => 'array',
        'auto_remediated' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $evt) {
            if (empty($evt->uuid)) {
                $evt->uuid = (string) Str::uuid();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'error' => 'orange',
            'warning' => 'yellow',
            default => 'blue',
        };
    }
}

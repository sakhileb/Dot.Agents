<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentMessage extends Model
{
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'session_id', 'role', 'content', 'tool_calls', 'tool_results',
        'metadata', 'token_count', 'cost', 'model_used', 'latency_ms',
        'is_edited', 'flagged', 'flag_reason',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'tool_results' => 'array',
        'metadata' => 'array',
        'cost' => 'decimal:6',
        'is_edited' => 'boolean',
        'flagged' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $msg) {
            if (empty($msg->uuid)) {
                $msg->uuid = (string) Str::uuid();
            }
            // Auto-inherit organization from the parent session when not explicitly set.
            // This keeps messages scoped to the same tenant as their session without
            // requiring callers to pass organization_id on every create().
            if (empty($msg->organization_id) && ! empty($msg->session_id)) {
                $msg->organization_id = AgentSession::where('id', $msg->session_id)
                    ->value('organization_id');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AgentSession::class);
    }
}

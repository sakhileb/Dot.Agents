<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'session_id', 'role', 'content', 'tool_calls', 'tool_results',
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
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AgentSession::class);
    }
}

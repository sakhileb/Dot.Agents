<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AgentSession extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'agent_deployment_id', 'organization_id', 'user_id',
        'session_type', 'title', 'status', 'context', 'metadata',
        'message_count', 'token_count', 'cost', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'cost' => 'decimal:6',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessage::class, 'session_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class, 'session_id');
    }
}

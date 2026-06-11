<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialMessage extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_conversation_id', 'agent_deployment_id',
        'platform_message_id', 'direction', 'sender_type', 'sender_id', 'sender_name',
        'content', 'media_attachments', 'message_type', 'status',
        'is_ai_generated', 'requires_approval', 'approval_status',
        'approved_by', 'approved_at', 'ai_confidence', 'ai_context',
        'was_disclosed_as_ai', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'media_attachments' => 'array',
        'ai_context' => 'array',
        'is_ai_generated' => 'boolean',
        'requires_approval' => 'boolean',
        'was_disclosed_as_ai' => 'boolean',
        'ai_confidence' => 'decimal:2',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
        static::created(function (self $model) {
            $model->socialConversation()->increment('message_count');
            $model->socialConversation()->update(['last_message_at' => now()]);
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function socialConversation(): BelongsTo
    {
        return $this->belongsTo(SocialConversation::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isPendingApproval(): bool
    {
        return $this->requires_approval && $this->approval_status === 'pending';
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialLead extends Model
{
    use HasFactory, HasOrganizationScope, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'social_conversation_id', 'agent_deployment_id',
        'platform', 'contact_platform_id', 'contact_name', 'contact_handle',
        'email', 'phone', 'company', 'job_title', 'location',
        'status', 'stage', 'intent_level', 'lead_score', 'intent_score', 'priority',
        'recommended_actions', 'crm_synced', 'crm_platform', 'crm_record_id', 'crm_synced_at',
        'custom_fields', 'interaction_history',
        'first_touch_at', 'last_touch_at', 'qualified_at', 'converted_at',
    ];

    protected $casts = [
        'lead_score' => 'decimal:2',
        'intent_score' => 'decimal:2',
        'recommended_actions' => 'array',
        'crm_synced' => 'boolean',
        'custom_fields' => 'array',
        'interaction_history' => 'array',
        'first_touch_at' => 'datetime',
        'last_touch_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'crm_synced_at' => 'datetime',
    ];

    protected $hidden = ['email', 'phone'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->first_touch_at)) {
                $model->first_touch_at = now();
            }
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

    public function conversions(): HasMany
    {
        return $this->hasMany(SocialConversion::class);
    }

    public function isQualified(): bool
    {
        return in_array($this->status, ['qualified', 'converted']);
    }

    public function isHot(): bool
    {
        return $this->priority === 'hot' || $this->lead_score >= 80;
    }

    public function isHighIntent(): bool
    {
        return in_array($this->intent_level, ['ready_to_buy', 'high_intent']);
    }
}

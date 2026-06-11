<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SocialConversion extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'social_lead_id', 'social_conversation_id',
        'agent_deployment_id', 'campaign_id',
        'conversion_type', 'platform', 'revenue', 'currency',
        'product_or_service', 'agent_attribution_score',
        'attribution_path', 'metadata', 'converted_at',
    ];

    protected $casts = [
        'revenue' => 'decimal:2',
        'agent_attribution_score' => 'decimal:2',
        'attribution_path' => 'array',
        'metadata' => 'array',
        'converted_at' => 'datetime',
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

    public function socialLead(): BelongsTo
    {
        return $this->belongsTo(SocialLead::class);
    }

    public function socialConversation(): BelongsTo
    {
        return $this->belongsTo(SocialConversation::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SocialCampaign::class, 'campaign_id');
    }

    public function hasRevenue(): bool
    {
        return $this->revenue !== null && $this->revenue > 0;
    }
}

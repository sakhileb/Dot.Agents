<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialSentimentScore extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id', 'social_account_id', 'social_conversation_id',
        'agent_deployment_id', 'subject_type', 'platform',
        'sentiment', 'score', 'confidence', 'summary',
        'detected_topics', 'detected_emotions',
        'requires_escalation', 'escalation_handled', 'scored_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'confidence' => 'decimal:2',
        'detected_topics' => 'array',
        'detected_emotions' => 'array',
        'requires_escalation' => 'boolean',
        'escalation_handled' => 'boolean',
        'scored_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function socialConversation(): BelongsTo
    {
        return $this->belongsTo(SocialConversation::class);
    }

    public function agentDeployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class);
    }

    public function isNegative(): bool
    {
        return in_array($this->sentiment, ['frustrated', 'angry', 'concerned']);
    }

    public function needsEscalation(): bool
    {
        return $this->requires_escalation && ! $this->escalation_handled;
    }
}

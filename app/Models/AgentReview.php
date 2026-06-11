<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'organization_id', 'user_id', 'deployment_id',
        'rating', 'title', 'body', 'dimension_scores',
        'is_verified', 'helpful_count', 'is_featured',
    ];

    protected $casts = [
        'pros' => 'array',
        'cons' => 'array',
        'rating' => 'decimal:1',
        'would_recommend' => 'boolean',
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}

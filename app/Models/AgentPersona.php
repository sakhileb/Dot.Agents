<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPersona extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'name', 'system_prompt', 'persona_description',
        'instructions', 'constraints', 'example_interactions',
        'temperature', 'max_tokens', 'is_default',
    ];

    protected $casts = [
        'system_prompt' => 'encrypted',
        'personality_traits' => 'array',
        'response_format' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

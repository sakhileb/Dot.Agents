<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'layer',
        'category',
        'class',
        'manifest',
        'is_active',
        'is_built_in',
        'requires_ai',
        'sort_order',
    ];

    protected $casts = [
        'manifest' => 'array',
        'is_active' => 'boolean',
        'is_built_in' => 'boolean',
        'requires_ai' => 'boolean',
    ];

    // ── Scopes ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLayer($query, string $layer)
    {
        return $query->where('layer', $layer);
    }

    public function scopeBuiltIn($query)
    {
        return $query->where('is_built_in', true);
    }

    // ── Relationships ────────────────────────────────────

    public function assignments(): HasMany
    {
        return $this->hasMany(AgentSkillAssignment::class, 'skill_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AgentSkillExecution::class, 'skill_id');
    }

    // ── Helpers ──────────────────────────────────────────

    /** True when this skill has a PHP implementation class. */
    public function hasImplementation(): bool
    {
        return ! empty($this->class) && class_exists($this->class);
    }

    /** Layer display label. */
    public function layerLabel(): string
    {
        return match ($this->layer) {
            'core' => 'Core Worker',
            'enterprise' => 'Enterprise Decision',
            'workforce' => 'Workforce',
            'governance' => 'Self-Governance',
            'platform' => 'Platform Intelligence',
            'meta' => 'Meta-Agent',
            default => ucfirst($this->layer),
        };
    }

    /** Layer badge color (Tailwind CSS class). */
    public function layerColor(): string
    {
        return match ($this->layer) {
            'core' => 'blue',
            'enterprise' => 'purple',
            'workforce' => 'yellow',
            'governance' => 'green',
            'platform' => 'orange',
            'meta' => 'red',
            default => 'gray',
        };
    }
}

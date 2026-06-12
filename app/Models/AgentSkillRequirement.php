<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSkillRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'requirement_type',
        'requirement_key',
        'requirement_label',
        'description',
        'is_required',
        'validation_config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'validation_config' => 'array',
    ];

    // Requirement types
    public const TYPE_DATA_SOURCE = 'data_source';

    public const TYPE_INTEGRATION = 'integration';

    public const TYPE_PERMISSION = 'permission';

    public const TYPE_CONFIG = 'config';

    public const TYPE_MODEL = 'model';

    // ── Relationships ────────────────────────────────────

    public function skill(): BelongsTo
    {
        return $this->belongsTo(AgentSkill::class, 'skill_id');
    }

    /**
     * Named scope that documents this model is an intentionally shared
     * platform-level catalog — NOT org-scoped. Skill requirements are
     * global definitions tied to AgentSkill records, not organizations.
     *
     * Usage: AgentSkillRequirement::platformCatalog()->where('skill_id', $id)->get();
     */
    public function scopePlatformCatalog($query)
    {
        return $query; // Intentionally shared — no organization_id filter
    }
}

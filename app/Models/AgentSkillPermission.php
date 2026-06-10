<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSkillPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'permission_key',
        'permission_label',
        'scope',
        'description',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────

    public function skill(): BelongsTo
    {
        return $this->belongsTo(AgentSkill::class, 'skill_id');
    }
}

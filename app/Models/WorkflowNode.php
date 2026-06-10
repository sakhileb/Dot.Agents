<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowNode extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'organization_id',
        'agent_key',
        'label',
        'position_x',
        'position_y',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $node) {
            if (empty($node->uuid)) {
                $node->uuid = (string) Str::uuid();
            }
        });
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AgentWorkflow::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Resolve display label — falls back to agent_key */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? $this->agent_key;
    }
}

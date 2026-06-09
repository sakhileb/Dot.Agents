<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'organization_id',
        'from_node_uuid',
        'to_node_uuid',
        'condition',
        'label',
    ];

    protected $casts = [
        'condition' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $conn) {
            if (empty($conn->uuid)) {
                $conn->uuid = (string) Str::uuid();
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

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'from_node_uuid', 'uuid');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'to_node_uuid', 'uuid');
    }

    /** Is this an unconditional edge? */
    public function isUnconditional(): bool
    {
        return empty($this->condition);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkflowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'workflow_id', 'organization_id', 'triggered_by', 'trigger_type', 'status',
        'input_data', 'output_data', 'step_results', 'error_message', 'resumed_data',
        'current_step', 'total_steps', 'total_cost', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'step_results' => 'array',
        'total_cost' => 'decimal:4',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $exec) {
            if (empty($exec->uuid)) {
                $exec->uuid = (string) Str::uuid();
            }
        });
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AgentWorkflow::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

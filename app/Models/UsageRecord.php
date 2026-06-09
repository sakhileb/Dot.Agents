<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id', 'agent_deployment_id', 'user_id',
        'record_type', 'recorded_date', 'message_count', 'task_count',
        'token_count', 'input_tokens', 'output_tokens',
        'compute_units', 'total_cost', 'currency', 'metadata',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'message_count' => 'integer',
        'task_count' => 'integer',
        'token_count' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'compute_units' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('recorded_date', $date);
    }

    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('recorded_date', [$start, $end]);
    }
}

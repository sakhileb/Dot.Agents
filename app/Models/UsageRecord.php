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
        'organization_id',
        'agent_deployment_id',
        'metric_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'model_used',
        'reference_type',
        'reference_id',
        'recorded_date',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:8',
        'total_cost' => 'decimal:4',
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

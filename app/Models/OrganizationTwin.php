<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Organization Twin
 *
 * A live model of the organization's structure, operations, and health.
 * Updated by the Enterprise Brain as agents execute tasks, workflows complete,
 * and organizational data flows in from integrated systems.
 */
class OrganizationTwin extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'departments_snapshot', 'people_summary',
        'active_projects', 'active_workflows', 'customer_segments',
        'vendor_categories', 'budget_allocation', 'monthly_ai_spend_usd',
        'estimated_ai_roi', 'workflow_patterns', 'bottlenecks',
        'optimization_opportunities', 'operational_health_score',
        'agent_utilization_rate', 'snapshot_at',
    ];

    protected $casts = [
        'departments_snapshot' => 'array',
        'people_summary' => 'array',
        'active_projects' => 'array',
        'active_workflows' => 'array',
        'customer_segments' => 'array',
        'vendor_categories' => 'array',
        'budget_allocation' => 'array',
        'workflow_patterns' => 'array',
        'bottlenecks' => 'array',
        'optimization_opportunities' => 'array',
        'monthly_ai_spend_usd' => 'decimal:2',
        'estimated_ai_roi' => 'decimal:2',
        'operational_health_score' => 'decimal:2',
        'agent_utilization_rate' => 'decimal:2',
        'snapshot_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

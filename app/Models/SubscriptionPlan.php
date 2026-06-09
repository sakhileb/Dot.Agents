<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_annually',
        'currency', 'max_agents', 'max_tasks_per_month', 'max_users',
        'max_storage_gb', 'features', 'is_active', 'is_public',
        'sort_order', 'metadata',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_annually' => 'decimal:2',
        'max_agents' => 'integer',
        'max_tasks_per_month' => 'integer',
        'max_users' => 'integer',
        'max_storage_gb' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

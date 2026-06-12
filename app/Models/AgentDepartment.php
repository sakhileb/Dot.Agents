<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Named scope that documents this model is an intentionally shared
     * platform-level catalog — NOT org-scoped. Departments are global
     * taxonomy data used across all organizations for agent classification.
     *
     * Usage: AgentDepartment::platformCatalog()->active()->ordered()->get();
     */
    public function scopePlatformCatalog($query)
    {
        return $query; // Intentionally shared — no organization_id filter
    }
}

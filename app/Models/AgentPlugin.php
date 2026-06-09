<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentPlugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'version',
        'description',
        'class',
        'manifest',
        'category',
        'price',
        'is_active',
        'is_featured',
        'organization_id',
    ];

    protected $casts = [
        'manifest' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        // Returns platform-wide plugins + org-specific ones
        return $query->where(function ($q) use ($organizationId) {
            $q->whereNull('organization_id')
                ->orWhere('organization_id', $organizationId);
        });
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(AgentPluginInstallation::class, 'plugin_id');
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /** Resolve a fresh instance of the plugin's implementation class */
    public function resolve(): mixed
    {
        if (! class_exists($this->class)) {
            throw new \RuntimeException("Plugin class [{$this->class}] not found for plugin [{$this->key}].");
        }

        return app($this->class);
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }
}

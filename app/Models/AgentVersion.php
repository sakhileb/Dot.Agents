<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentVersion extends Model
{
    protected $fillable = [
        'agent_id', 'created_by', 'version', 'status',
        'release_notes', 'config_snapshot', 'capabilities_snapshot',
        'is_current', 'published_at', 'deprecated_at',
    ];

    protected $casts = [
        'config_snapshot' => 'array',
        'capabilities_snapshot' => 'array',
        'is_current' => 'boolean',
        'published_at' => 'datetime',
        'deprecated_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(AgentDeployment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Publish this version and demote the previously current version.
     */
    public function publish(): void
    {
        // Demote existing current version
        static::where('agent_id', $this->agent_id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $this->update([
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Deprecate this version (no longer recommended for new deployments).
     */
    public function deprecate(): void
    {
        $this->update([
            'status' => 'deprecated',
            'is_current' => false,
            'deprecated_at' => now(),
        ]);
    }
}

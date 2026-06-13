<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'user_id', 'role',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'joined_at' => 'datetime',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('team_id', $organizationId);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['owner', 'admin']);
    }
}

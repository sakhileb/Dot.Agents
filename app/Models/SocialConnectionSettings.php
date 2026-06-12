<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialConnectionSettings extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id',
        'social_account_id',
        'platform',
        'goals',
        'ai_features',
        'permissions',
        'autonomy_level',
        'status',
    ];

    protected $casts = [
        'goals' => 'array',
        'ai_features' => 'array',
        'permissions' => 'array',
        'autonomy_level' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}

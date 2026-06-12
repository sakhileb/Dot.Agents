<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSocialCredential extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id',
        'platform',
        'client_id',
        'client_secret',
        'redirect_uri',
        'extra_config',
        'updated_by',
    ];

    protected $casts = [
        'client_id' => 'encrypted',
        'client_secret' => 'encrypted',
        'extra_config' => 'array',
    ];

    protected $hidden = ['client_id', 'client_secret'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Return a config array compatible with Socialite::buildProvider().
     */
    public function toSocialiteConfig(string $fallbackRedirect): array
    {
        return [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect' => $this->redirect_uri ?? $fallbackRedirect,
        ];
    }
}

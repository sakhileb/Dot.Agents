<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPluginInstallation extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'plugin_id',
        'organization_id',
        'installed_by',
        'config',
        'installed_at',
    ];

    protected $casts = [
        'config' => 'encrypted:array', // Plugin configs may contain API keys / credentials
        'installed_at' => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(AgentPlugin::class, 'plugin_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}

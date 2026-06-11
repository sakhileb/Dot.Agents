<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialEngagement extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id', 'social_page_id', 'social_post_id',
        'platform', 'engagement_type', 'actor_platform_id', 'actor_name',
        'count', 'engagement_date', 'metadata',
    ];

    protected $casts = [
        'count' => 'integer',
        'metadata' => 'array',
        'engagement_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function socialPage(): BelongsTo
    {
        return $this->belongsTo(SocialPage::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }
}

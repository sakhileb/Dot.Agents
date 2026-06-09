<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'knowledge_base_id', 'organization_id', 'author_id',
        'title', 'slug', 'content', 'summary', 'tags',
        'category', 'status', 'source_type', 'source_url',
        'embedding', 'view_count', 'helpful_count', 'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'embedding' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}

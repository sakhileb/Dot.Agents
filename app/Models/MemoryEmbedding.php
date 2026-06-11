<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Memory Embedding
 *
 * Stores vector embeddings for semantic memory retrieval (Level 5 — Enterprise Memory Cortex).
 * Every AgentMemory record can have a corresponding embedding for cosine-similarity search.
 *
 * The embedding column stores a float array (OpenAI text-embedding-3-small = 1536 dimensions).
 * In production, this can be migrated to pgvector (VECTOR type) for sub-millisecond ANN search.
 */
class MemoryEmbedding extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'uuid', 'organization_id', 'agent_deployment_id',
        'embeddable_type', 'embeddable_id',
        'content_hash', 'content_preview', 'memory_type', 'subject',
        'embedding', 'embedding_dimensions', 'embedding_model',
        'importance_score', 'expires_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'importance_score' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_deployment_id');
    }

    public function embeddable()
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}

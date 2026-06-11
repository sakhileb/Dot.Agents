<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\MemoryEmbedding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

/**
 * Vector Memory Service — Level 5 Enterprise Memory Cortex
 *
 * Replaces the keyword-based MemoryService::getRelevantMemories() with
 * OpenAI embedding-based cosine similarity search.
 *
 * Architecture:
 *   1. When a memory is stored → embed content → persist to memory_embeddings
 *   2. When querying → embed the query → cosine similarity against stored vectors
 *   3. Returns semantically relevant memories ranked by similarity score
 *
 * Production upgrade path:
 *   - MySQL 8.0: JSON embedding column (current implementation)
 *   - PostgreSQL + pgvector: ALTER COLUMN embedding TYPE VECTOR(1536) → ANN index
 *   - Redis: Use redis/redisvl for HNSW vector search
 *
 * Fallback: If OpenAI embedding call fails, delegates to keyword scoring.
 */
class VectorMemoryService
{
    private const EMBEDDING_MODEL = 'text-embedding-3-small';

    private const EMBEDDING_DIMENSIONS = 1536;

    private const SIMILARITY_THRESHOLD = 0.72;

    private const EMBEDDING_CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly MemoryService $fallbackService,
    ) {}

    /**
     * Retrieve semantically relevant memories for a deployment using vector similarity.
     *
     * Falls back to keyword scoring if embeddings are unavailable.
     */
    public function getRelevantMemories(AgentDeployment $deployment, string $input, int $limit = 5): array
    {
        try {
            $queryEmbedding = $this->embedText($input);

            if (empty($queryEmbedding)) {
                return $this->keywordFallback($deployment, $input, $limit);
            }

            return $this->searchByVector($deployment, $queryEmbedding, $limit);
        } catch (Throwable $e) {
            Log::warning('[VectorMemoryService] Vector search failed, using keyword fallback', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            return $this->keywordFallback($deployment, $input, $limit);
        }
    }

    /**
     * Embed and store a memory for future semantic retrieval.
     * Called whenever a new AgentMemory is created.
     */
    public function embedAndStoreMemory(AgentMemory $memory): ?MemoryEmbedding
    {
        $contentHash = hash('sha256', $memory->content ?? '');

        // Skip if already embedded (idempotent)
        $existing = MemoryEmbedding::where('content_hash', $contentHash)
            ->where('agent_deployment_id', $memory->agent_deployment_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            $textToEmbed = trim(($memory->subject ? $memory->subject.': ' : '').$memory->content);
            $embedding = $this->embedText($textToEmbed);

            if (empty($embedding)) {
                return null;
            }

            return MemoryEmbedding::create([
                'organization_id' => $memory->organization_id,
                'agent_deployment_id' => $memory->agent_deployment_id,
                'embeddable_type' => AgentMemory::class,
                'embeddable_id' => $memory->id,
                'content_hash' => $contentHash,
                'content_preview' => substr($textToEmbed, 0, 500),
                'memory_type' => $memory->memory_type,
                'subject' => $memory->subject,
                'embedding' => $embedding,
                'embedding_dimensions' => self::EMBEDDING_DIMENSIONS,
                'embedding_model' => self::EMBEDDING_MODEL,
                'importance_score' => $memory->importance_score,
                'expires_at' => $memory->expires_at,
            ]);
        } catch (Throwable $e) {
            Log::warning('[VectorMemoryService] Failed to embed memory', [
                'memory_id' => $memory->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search memory embeddings by vector similarity (cosine similarity).
     */
    private function searchByVector(AgentDeployment $deployment, array $queryVector, int $limit): array
    {
        $embeddings = MemoryEmbedding::where('agent_deployment_id', $deployment->id)
            ->active()
            ->get();

        if ($embeddings->isEmpty()) {
            return $this->keywordFallback($deployment, '', $limit);
        }

        $scored = $embeddings
            ->map(function (MemoryEmbedding $embedding) use ($queryVector) {
                $similarity = $this->cosineSimilarity($queryVector, $embedding->embedding);

                return ['embedding' => $embedding, 'similarity' => $similarity];
            })
            ->filter(fn ($item) => $item['similarity'] >= self::SIMILARITY_THRESHOLD)
            ->sortByDesc('similarity')
            ->take($limit);

        return $scored->map(fn ($item) => [
            'type' => $item['embedding']->memory_type,
            'subject' => $item['embedding']->subject,
            'content' => $item['embedding']->content_preview,
            'importance' => $item['embedding']->importance_score,
            'similarity_score' => round($item['similarity'], 4),
        ])->values()->toArray();
    }

    /**
     * Get the embedding vector for a text using OpenAI.
     * Caches embeddings for 1 hour to reduce API calls.
     * Returns empty array in testing environment (no real API calls in tests).
     */
    public function embedText(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        // Never call OpenAI in testing — embeddings should be seeded via cache in tests
        if (app()->environment('testing')) {
            $cacheKey = 'embed_'.hash('sha256', $text);

            return Cache::get($cacheKey, []);
        }

        $cacheKey = 'embed_'.hash('sha256', $text);

        return Cache::remember($cacheKey, self::EMBEDDING_CACHE_TTL, function () use ($text) {
            $response = OpenAI::embeddings()->create([
                'model' => self::EMBEDDING_MODEL,
                'input' => substr($text, 0, 8191), // OpenAI token limit
            ]);

            return $response->embeddings[0]->embedding ?? [];
        });
    }

    /**
     * Keyword-based fallback — delegates to MemoryService::keywordSearch().
     * Does NOT call getRelevantMemories() to prevent circular recursion.
     */
    private function keywordFallback(AgentDeployment $deployment, string $input, int $limit): array
    {
        return $this->fallbackService->keywordSearch($deployment, $input, $limit);
    }

    /**
     * Compute cosine similarity between two float vectors.
     *
     * Result: 1.0 = identical, 0.0 = orthogonal, -1.0 = opposite
     * For semantic similarity, values ≥ 0.72 are considered relevant.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || empty($a)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        $count = count($a);
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] ** 2;
            $magnitudeB += $b[$i] ** 2;
        }

        $denominator = sqrt($magnitudeA) * sqrt($magnitudeB);

        return $denominator > 0 ? $dotProduct / $denominator : 0.0;
    }
}

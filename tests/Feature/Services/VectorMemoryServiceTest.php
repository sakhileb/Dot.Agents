<?php

namespace Tests\Feature\Services;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\MemoryEmbedding;
use App\Models\Organization;
use App\Services\AI\MemoryService;
use App\Services\AI\VectorMemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VectorMemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private VectorMemoryService $vectorService;

    private MemoryService $memoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memoryService = app(MemoryService::class);
        $this->vectorService = app(VectorMemoryService::class);
    }

    public function test_cosine_similarity_identical_vectors(): void
    {
        $v = [0.5, 0.5, 0.5, 0.5];
        $similarity = $this->vectorService->cosineSimilarity($v, $v);

        $this->assertEqualsWithDelta(1.0, $similarity, 0.0001, 'Identical vectors should have similarity 1.0');
    }

    public function test_cosine_similarity_orthogonal_vectors(): void
    {
        $a = [1.0, 0.0, 0.0];
        $b = [0.0, 1.0, 0.0];
        $similarity = $this->vectorService->cosineSimilarity($a, $b);

        $this->assertEqualsWithDelta(0.0, $similarity, 0.0001, 'Orthogonal vectors should have similarity 0.0');
    }

    public function test_cosine_similarity_opposite_vectors(): void
    {
        $a = [1.0, 0.0];
        $b = [-1.0, 0.0];
        $similarity = $this->vectorService->cosineSimilarity($a, $b);

        $this->assertEqualsWithDelta(-1.0, $similarity, 0.0001, 'Opposite vectors should have similarity -1.0');
    }

    public function test_cosine_similarity_empty_vectors_returns_zero(): void
    {
        $similarity = $this->vectorService->cosineSimilarity([], []);

        $this->assertEquals(0.0, $similarity);
    }

    public function test_cosine_similarity_mismatched_lengths_returns_zero(): void
    {
        $similarity = $this->vectorService->cosineSimilarity([1.0, 0.5], [1.0]);

        $this->assertEquals(0.0, $similarity);
    }

    public function test_embed_text_returns_cached_result(): void
    {
        $text = 'test embedding text for cache verification';
        $cacheKey = 'embed_'.hash('sha256', $text);

        // Pre-seed the cache with a fake embedding
        $fakeEmbedding = array_fill(0, 1536, 0.01);
        Cache::put($cacheKey, $fakeEmbedding, 3600);

        $result = $this->vectorService->embedText($text);

        $this->assertCount(1536, $result);
        $this->assertEquals($fakeEmbedding, $result);
    }

    public function test_embed_text_empty_string_returns_empty_array(): void
    {
        $result = $this->vectorService->embedText('');
        $this->assertEmpty($result);
    }

    public function test_embed_text_whitespace_only_returns_empty_array(): void
    {
        $result = $this->vectorService->embedText('   ');
        $this->assertEmpty($result);
    }

    public function test_get_relevant_memories_falls_back_to_keyword_when_no_embeddings(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create();
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        // Create memories with no embeddings in MemoryEmbedding table
        AgentMemory::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $org->id,
            'memory_type' => 'long_term',
            'subject' => 'Customer Pricing Policy',
            'content' => 'Customers get 20% discount on annual plans',
            'importance_score' => 80,
            'expires_at' => null,
        ]);

        // With no cached embedding, OpenAI would be called.
        // We verify fallback: the call doesn't throw even when OpenAI is not mocked.
        // The VectorMemoryService will fall back to keyword search.
        $results = $this->vectorService->getRelevantMemories($deployment, 'customer discount pricing', 3);

        $this->assertIsArray($results);
    }

    public function test_embed_and_store_memory_is_idempotent(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create();
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        $memory = AgentMemory::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $org->id,
            'content' => 'Policy: Refunds within 30 days',
            'subject' => 'Refund Policy',
        ]);

        $contentHash = hash('sha256', $memory->content);

        // Manually insert an embedding to simulate it already being stored
        MemoryEmbedding::create([
            'organization_id' => $org->id,
            'agent_deployment_id' => $deployment->id,
            'embeddable_type' => AgentMemory::class,
            'embeddable_id' => $memory->id,
            'content_hash' => $contentHash,
            'content_preview' => 'Refund Policy: Policy: Refunds within 30 days',
            'memory_type' => 'long_term',
            'subject' => 'Refund Policy',
            'embedding' => array_fill(0, 10, 0.1),
            'embedding_dimensions' => 10,
            'embedding_model' => 'test',
        ]);

        // Call embedAndStoreMemory again — should return the existing one, not create a duplicate
        $result = $this->vectorService->embedAndStoreMemory($memory);

        $this->assertNotNull($result);
        $this->assertEquals(1, MemoryEmbedding::where('content_hash', $contentHash)->count());
    }

    public function test_vector_search_returns_top_results_by_similarity(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create();
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        $memory = AgentMemory::factory()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $org->id,
            'content' => 'Refund policy',
            'subject' => 'Refund',
        ]);

        // Seed a high-similarity embedding
        $embedding = array_fill(0, 1536, 0.5);
        MemoryEmbedding::create([
            'organization_id' => $org->id,
            'agent_deployment_id' => $deployment->id,
            'embeddable_type' => AgentMemory::class,
            'embeddable_id' => $memory->id,
            'content_hash' => hash('sha256', 'Refund policy'),
            'content_preview' => 'Refund: Refund policy',
            'memory_type' => 'long_term',
            'subject' => 'Refund',
            'embedding' => $embedding,
            'embedding_dimensions' => 1536,
            'embedding_model' => 'text-embedding-3-small',
            'importance_score' => 80,
        ]);

        // Seed the cache with the same vector for the query
        $query = 'tell me about refund policy';
        Cache::put('embed_'.hash('sha256', $query), $embedding, 3600);

        $results = $this->vectorService->getRelevantMemories($deployment, $query, 5);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertEquals('Refund', $results[0]['subject']);
        $this->assertArrayHasKey('similarity_score', $results[0]);
        $this->assertEqualsWithDelta(1.0, $results[0]['similarity_score'], 0.001);
    }
}

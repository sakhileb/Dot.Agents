<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dot.OS™ Adaptive Enterprise Consciousness (AEC) v2.0 — Core Schema
 *
 * Introduces:
 *   1. organization_dna            — Enterprise Constitution Engine (mission, values, principles)
 *   2. executive_council_sessions  — Digital Executive Council deliberation sessions
 *   3. executive_recommendations   — Per-domain executive recommendations from council
 *   4. executive_votes             — Agent votes on strategic decisions
 *   5. organization_twins          — Organizational Digital Twin models
 *   6. memory_embeddings           — Vector embedding store for semantic memory (Level 5)
 *   7. organizational_embeddings   — Cross-org knowledge vectors
 *   8. enterprise_decisions        — Enterprise Decision Intelligence permanent store
 *   9. enterprise_health_scores    — Enterprise Health System (8 health domains)
 *   10. agent_memory.embedding     — Adds embedding column to existing agent_memories
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. Enterprise Constitution / Organization DNA
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('organization_dna', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();

            // Identity
            $table->string('mission')->nullable();
            $table->string('vision')->nullable();
            $table->text('values')->nullable()
                ->comment('JSON array of company value strings');

            // Principles (inherited by ALL agents automatically)
            $table->json('leadership_principles')->nullable()
                ->comment('["Customer Obsession","Safety Before Production",...]');
            $table->json('decision_principles')->nullable()
                ->comment('Principles governing every agent decision');
            $table->string('risk_appetite')->default('moderate')
                ->comment('conservative | moderate | aggressive | calculated');

            // Strategic configuration
            $table->json('compliance_requirements')->nullable()
                ->comment('["GDPR","SOC2","HIPAA",...]');
            $table->json('strategic_priorities')->nullable()
                ->comment('Ordered list of strategic focus areas');
            $table->json('industry_constraints')->nullable()
                ->comment('Industry-specific rules: finance, healthcare, etc.');

            // Operational context
            $table->string('primary_language', 10)->default('en');
            $table->json('operating_regions')->nullable();
            $table->string('fiscal_year_start', 5)->default('01-01')
                ->comment('MM-DD format');
            $table->decimal('monthly_ai_budget_usd', 10, 2)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 2. Digital Executive Council Sessions
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('executive_council_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('session_type')
                ->comment('strategic_decision | budget_approval | risk_review | policy_change | crisis_response');
            $table->string('title');
            $table->text('context');
            $table->string('status')->default('pending')
                ->comment('pending | deliberating | completed | aborted');

            // Decision context
            $table->json('input_data')->nullable();
            $table->json('constraints')->nullable();
            $table->decimal('financial_threshold', 15, 2)->nullable()
                ->comment('USD amount involved, determines council quorum');

            // Outputs
            $table->json('consensus_recommendation')->nullable();
            $table->decimal('consensus_confidence', 5, 2)->nullable();
            $table->string('final_decision')->nullable()
                ->comment('approved | rejected | deferred | modified');
            $table->text('rationale')->nullable();

            $table->integer('agents_consulted')->default(0);
            $table->integer('votes_cast')->default(0);
            $table->integer('votes_for')->default(0);
            $table->integer('votes_against')->default(0);

            $table->timestamp('deliberation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('deliberation_duration_seconds')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'session_type']);
        });

        Schema::create('executive_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('executive_council_sessions')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('agent_role')
                ->comment('ceo | cfo | coo | cto | cio | chro | cmo | ciso | cso');
            $table->string('agent_deployment_id_ref')->nullable()
                ->comment('Optional FK to actual deployed executive agent');

            $table->string('domain')
                ->comment('strategic | financial | operational | security | human | compliance');
            $table->text('recommendation');
            $table->json('impact_analysis')->nullable()
                ->comment('{strategic_impact, financial_impact, risk_impact, timeline}');
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->decimal('risk_score', 5, 2)->default(0);

            $table->json('evidence')->nullable();
            $table->json('alternatives')->nullable();
            $table->string('vote')->nullable()
                ->comment('for | against | abstain | conditional');
            $table->text('vote_rationale')->nullable();

            $table->timestamps();

            $table->index(['session_id', 'domain']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 3. Organizational Digital Twin
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('organization_twins', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();

            // Snapshot of organizational structure
            $table->json('departments_snapshot')->nullable();
            $table->json('people_summary')->nullable()
                ->comment('Anonymized headcount and role distribution');
            $table->json('active_projects')->nullable();
            $table->json('active_workflows')->nullable();
            $table->json('customer_segments')->nullable();
            $table->json('vendor_categories')->nullable();

            // Budget & financial twin
            $table->json('budget_allocation')->nullable()
                ->comment('Department → budget percentage map');
            $table->decimal('monthly_ai_spend_usd', 10, 2)->default(0);
            $table->decimal('estimated_ai_roi', 5, 2)->default(0)
                ->comment('Multiplier: 1.0 = break-even, 2.5 = 2.5x return');

            // Operational patterns
            $table->json('workflow_patterns')->nullable()
                ->comment('Detected recurring workflow patterns');
            $table->json('bottlenecks')->nullable();
            $table->json('optimization_opportunities')->nullable();

            // Health indicators
            $table->decimal('operational_health_score', 5, 2)->default(0);
            $table->decimal('agent_utilization_rate', 5, 2)->default(0);

            $table->timestamp('snapshot_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 4. Vector Memory Embeddings (Level 5 — Semantic Memory Cortex)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('memory_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            // Source reference — polymorphic to AgentMemory, DecisionLog, KnowledgeArticle, etc.
            $table->string('embeddable_type');
            $table->unsignedBigInteger('embeddable_id');

            $table->string('content_hash', 64)
                ->comment('SHA-256 of the original content. Deduplicates re-embedding.');
            $table->text('content_preview')
                ->comment('First 500 chars — shown in search results without decryption');
            $table->string('memory_type')
                ->comment('short_term | long_term | organizational | episodic | semantic');
            $table->string('subject')->nullable();

            // The embedding vector stored as a JSON float array
            // MySQL/SQLite: JSON; PostgreSQL with pgvector: VECTOR(1536)
            $table->json('embedding')
                ->comment('OpenAI text-embedding-3-small produces 1536-dimensional float vector');
            $table->unsignedSmallInteger('embedding_dimensions')->default(1536);
            $table->string('embedding_model')->default('text-embedding-3-small');

            $table->decimal('importance_score', 5, 2)->default(50);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'agent_deployment_id']);
            $table->index(['embeddable_type', 'embeddable_id']);
            $table->index('content_hash');
        });

        Schema::create('organizational_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Cross-agent organizational knowledge
            $table->string('knowledge_type')
                ->comment('policy | pattern | decision | procedure | insight | regulation');
            $table->string('subject');
            $table->text('content_preview');
            $table->string('content_hash', 64)->unique();
            $table->json('embedding');
            $table->string('embedding_model')->default('text-embedding-3-small');
            $table->decimal('authority_score', 5, 2)->default(70)
                ->comment('How authoritative is this knowledge? 0-100');
            $table->boolean('is_verified')->default(false);
            $table->integer('access_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'knowledge_type']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 5. Enterprise Decision Intelligence
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('enterprise_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('council_session_id')
                ->nullable()->constrained('executive_council_sessions')->nullOnDelete();

            $table->string('decision_category')
                ->comment('strategic | financial | operational | hr | legal | technical | security | social_commerce');
            $table->string('title');
            $table->text('context');
            $table->json('reasoning_chain')
                ->comment('Step-by-step reasoning trace');
            $table->json('evidence');
            $table->json('alternatives_considered');
            $table->json('expected_outcomes');

            $table->decimal('confidence_score', 5, 2);
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->decimal('financial_impact_usd', 15, 2)->nullable();
            $table->string('time_horizon')
                ->comment('immediate | short_term | medium_term | long_term');

            $table->string('status')->default('recorded')
                ->comment('recorded | implemented | monitoring | outcome_measured | reversed');
            $table->json('actual_outcomes')->nullable();
            $table->decimal('outcome_accuracy', 5, 2)->nullable()
                ->comment('How accurately did predicted outcomes match actuals? 0-100');

            $table->timestamps();

            $table->index(['organization_id', 'decision_category']);
            $table->index(['organization_id', 'status']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 6. Enterprise Health Scores
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('enterprise_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('scored_at');

            // 8 health domains (0-100 each)
            $table->decimal('revenue_health', 5, 2)->default(0);
            $table->decimal('customer_health', 5, 2)->default(0);
            $table->decimal('security_health', 5, 2)->default(0);
            $table->decimal('agent_health', 5, 2)->default(0);
            $table->decimal('workflow_health', 5, 2)->default(0);
            $table->decimal('compliance_health', 5, 2)->default(0);
            $table->decimal('operational_health', 5, 2)->default(0);
            $table->decimal('technology_health', 5, 2)->default(0);

            $table->decimal('enterprise_health_score', 5, 2)->default(0)
                ->comment('Weighted composite of all 8 domains');

            $table->json('domain_details')->nullable();
            $table->json('alerts')->nullable();
            $table->json('recommendations')->nullable();

            $table->timestamps();

            $table->unique(['organization_id', 'scored_at']);
            $table->index(['organization_id', 'scored_at']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 7. Add embedding column to agent_memories (Level 5 upgrade)
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('agent_memories', function (Blueprint $table) {
            $table->json('embedding')->nullable()->after('tags')
                ->comment('Vector embedding for semantic retrieval. NULL = not yet embedded.');
            $table->string('embedding_model', 100)->nullable()->after('embedding')
                ->comment('Model used to generate this embedding, e.g. text-embedding-3-small');
            $table->string('content_hash', 64)->nullable()->after('embedding_model')
                ->comment('SHA-256 of content for deduplication');
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('agent_memories', function (Blueprint $table) {
            $table->dropIndex(['content_hash']);
            $table->dropColumn(['embedding', 'embedding_model', 'content_hash']);
        });

        Schema::dropIfExists('enterprise_health_scores');
        Schema::dropIfExists('enterprise_decisions');
        Schema::dropIfExists('organizational_embeddings');
        Schema::dropIfExists('memory_embeddings');
        Schema::dropIfExists('organization_twins');
        Schema::dropIfExists('executive_recommendations');
        Schema::dropIfExists('executive_council_sessions');
        Schema::dropIfExists('organization_dna');
    }
};

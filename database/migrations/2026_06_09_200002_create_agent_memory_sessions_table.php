<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent memory system
        Schema::create('agent_memories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('memory_type'); // short_term, long_term, organizational
            $table->string('memory_category')->nullable(); // decision, lesson, preference, knowledge, interaction
            $table->string('subject')->nullable();
            $table->longText('content');
            $table->json('context')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('importance_score', 5, 2)->default(50.00);
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->integer('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_deployment_id', 'memory_type']);
            $table->index(['organization_id', 'memory_type']);
        });

        // Organizational knowledge base
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type')->default('general'); // general, department, policy, procedure, product
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->json('accessible_agents')->nullable(); // null means all agents
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->json('tags')->nullable();
            $table->string('category')->nullable();
            $table->string('source_type')->nullable(); // manual, uploaded, scraped, generated
            $table->string('source_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->decimal('relevance_score', 5, 2)->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Agent sessions / conversations
        Schema::create('agent_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_type')->default('conversation'); // conversation, task, workflow, analysis
            $table->string('title')->nullable();
            $table->string('status')->default('active'); // active, completed, abandoned, escalated
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('message_count')->default(0);
            $table->integer('token_count')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['agent_deployment_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Individual messages in a session
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('session_id')->constrained('agent_sessions')->cascadeOnDelete();
            $table->string('role'); // user, assistant, system, tool
            $table->longText('content');
            $table->json('tool_calls')->nullable();
            $table->json('tool_results')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('token_count')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->string('model_used')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->boolean('flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
        Schema::dropIfExists('agent_sessions');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_bases');
        Schema::dropIfExists('agent_memories');
    }
};

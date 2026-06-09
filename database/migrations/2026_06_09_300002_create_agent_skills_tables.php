<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master skill registry — all available skills across all 5 layers + meta
        Schema::create('agent_skills', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();            // e.g. 'workforce-orchestration'
            $table->string('name');
            $table->text('description')->nullable();

            // Layer taxonomy
            $table->string('layer');                    // core|enterprise|workforce|governance|platform|meta
            $table->string('category');                 // analysis|content|research|communication|strategic|...

            // Implementation binding (null = prompt-driven only)
            $table->string('class')->nullable();        // fully-qualified PHP class

            // Input/output schema + configurable defaults
            $table->json('manifest')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_built_in')->default(false);   // ships with platform
            $table->boolean('requires_ai')->default(false);   // calls an LLM internally
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['layer', 'category', 'is_active']);
        });

        // Per-deployment skill assignments — which skills are enabled per agent instance
        Schema::create('agent_skill_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();          // skill-specific overrides per deployment

            $table->timestamps();

            $table->unique(['agent_deployment_id', 'skill_id'], 'unique_deployment_skill');
            $table->index(['agent_deployment_id', 'is_enabled']);
        });

        // Execution audit trail — every skill invocation recorded
        Schema::create('agent_skill_executions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('agent_tasks')->nullOnDelete();

            $table->string('trigger');                  // pre_task|post_task|on_demand|scheduled|delegated
            $table->string('status');                   // pending|running|completed|failed|skipped
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('findings')->nullable();       // issues / flags raised
            $table->decimal('confidence', 5, 2)->nullable();
            $table->integer('duration_ms')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['agent_deployment_id', 'skill_id', 'status']);
            $table->index(['organization_id', 'trigger', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_skill_executions');
        Schema::dropIfExists('agent_skill_assignments');
        Schema::dropIfExists('agent_skills');
    }
};

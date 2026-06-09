<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Graph nodes — one per agent step on the canvas
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('agent_workflows')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('agent_key');            // matches Agent::key or AgentPlugin::key
            $table->string('label')->nullable();    // display label override

            // Canvas position
            $table->integer('position_x')->default(100);
            $table->integer('position_y')->default(100);

            // Runtime config per node
            $table->json('config')->nullable();     // overrides, model, temperature, etc.

            $table->timestamps();

            $table->index(['workflow_id', 'agent_key']);
        });

        // Graph edges — directed connections between nodes
        Schema::create('workflow_connections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('agent_workflows')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Node references by uuid (loose coupling, resolved at runtime)
            $table->string('from_node_uuid');
            $table->string('to_node_uuid');

            // Stored as JSON rule object: { "status": "completed" }
            // or { "min_confidence": 0.7 } or null (unconditional)
            $table->json('condition')->nullable();

            $table->string('label')->nullable();    // edge label on canvas

            $table->timestamps();

            $table->index(['workflow_id', 'from_node_uuid']);
        });

        // Agent plugin registry — installable / swappable agent implementations
        Schema::create('agent_plugins', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();        // e.g. "summarizer-v2"
            $table->string('name');
            $table->string('version')->default('1.0.0');
            $table->text('description')->nullable();

            // Fully-qualified class name of the implementation
            $table->string('class');

            // Capabilities, input/output schema, config schema
            $table->json('manifest')->nullable();

            $table->string('category')->default('general');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Organisation-scoped installations (null = platform-wide)
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Track which organisations have installed which plugins
        Schema::create('agent_plugin_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('agent_plugins')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installed_by')->constrained('users')->cascadeOnDelete();
            $table->json('config')->nullable();     // org-specific config overrides
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['plugin_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_plugin_installations');
        Schema::dropIfExists('agent_plugins');
        Schema::dropIfExists('workflow_connections');
        Schema::dropIfExists('workflow_nodes');
    }
};

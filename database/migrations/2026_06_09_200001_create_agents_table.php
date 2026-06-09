<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent categories for marketplace
        Schema::create('agent_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable(); // heroicon name
            $table->string('color')->nullable(); // tailwind color class
            $table->text('description')->nullable();
            $table->string('industry')->nullable(); // cross-industry, finance, healthcare, etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agent department classifications
        Schema::create('agent_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Master agent registry
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('category_id')->constrained('agent_categories')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('agent_departments')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->longText('full_description')->nullable();
            $table->string('avatar')->nullable();
            $table->string('version')->default('1.0.0');

            // Classification
            $table->string('agent_type'); // advisory, analytical, operational, executive, specialist
            $table->string('specialization')->nullable();
            $table->json('industries')->nullable(); // applicable industries
            $table->json('functions')->nullable(); // applicable business functions

            // AI Model configuration
            $table->string('primary_model')->default('gpt-4o'); // openai, anthropic, gemini, ollama
            $table->string('model_provider')->default('openai');
            $table->string('fallback_model')->nullable();
            $table->json('model_config')->nullable();

            // Capabilities & Skills
            $table->json('capabilities')->nullable(); // list of capability strings
            $table->json('limitations')->nullable();
            $table->json('skills')->nullable();
            $table->json('tools')->nullable(); // external tools this agent can use
            $table->json('integrations')->nullable(); // supported integrations
            $table->json('knowledge_areas')->nullable();
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable(); // supported languages

            // Operating model
            $table->json('goals')->nullable();
            $table->json('objectives')->nullable();
            $table->json('kpis')->nullable();
            $table->json('decision_framework')->nullable();
            $table->json('risk_controls')->nullable();
            $table->string('default_deployment_mode')->default('advisory'); // advisory, semi-autonomous, autonomous

            // Pricing
            $table->string('pricing_model')->default('subscription'); // subscription, usage, one-time
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('price_per_message', 10, 4)->nullable();
            $table->decimal('price_per_task', 10, 2)->nullable();
            $table->string('billing_cycle')->default('monthly');

            // Metrics & Quality
            $table->decimal('accuracy_score', 5, 2)->default(0);
            $table->decimal('reliability_score', 5, 2)->default(0);
            $table->decimal('satisfaction_score', 5, 2)->default(0);
            $table->integer('total_deployments')->default(0);
            $table->integer('total_tasks_completed')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);

            // Status & Visibility
            $table->string('status')->default('active'); // draft, active, deprecated, archived
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_enterprise_only')->default(false);
            $table->boolean('is_beta')->default(false);
            $table->json('required_plan')->nullable(); // minimum plan required

            // SEO & Discovery
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['department_id', 'status']);
            $table->index(['category_id', 'status']);
        });

        // Agent system prompt / persona configuration
        Schema::create('agent_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('default');
            $table->text('system_prompt');
            $table->text('persona_description')->nullable();
            $table->json('instructions')->nullable();
            $table->json('constraints')->nullable();
            $table->json('example_interactions')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_tokens')->default(4096);
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        // Deployed agent instances per organization
        Schema::create('agent_deployments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deployed_by')->constrained('users')->cascadeOnDelete();

            $table->string('name'); // custom name for this deployment
            $table->string('alias')->nullable(); // custom alias, e.g., "Our Marketing Bot"
            $table->text('custom_instructions')->nullable();
            $table->string('deployment_mode')->default('advisory'); // advisory, semi-autonomous, autonomous, executive_approval
            $table->string('status')->default('active'); // active, paused, suspended, decommissioned
            $table->boolean('requires_human_approval')->default(false);
            $table->decimal('confidence_threshold', 5, 2)->default(75.00); // below this requires approval

            // AI model override for this deployment
            $table->string('model_override')->nullable();
            $table->json('model_config_override')->nullable();

            // Context & Memory configuration
            $table->json('context_config')->nullable();
            $table->boolean('enable_memory')->default(true);
            $table->boolean('enable_long_term_memory')->default(true);
            $table->integer('memory_retention_days')->default(90);

            // Risk & Governance
            $table->decimal('risk_tolerance', 5, 2)->default(50.00);
            $table->json('allowed_actions')->nullable();
            $table->json('restricted_actions')->nullable();
            $table->json('data_access_scope')->nullable();

            $table->json('custom_kpis')->nullable();
            $table->json('notification_config')->nullable();
            $table->json('integration_config')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('decommissioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_deployments');
        Schema::dropIfExists('agent_personas');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('agent_departments');
        Schema::dropIfExists('agent_categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_mega_scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Composite score
            $table->decimal('final_score', 5, 2)->default(0);
            $table->string('certification')->default('HIGH RISK');
            $table->tinyInteger('level')->default(1);
            $table->boolean('gate_pass')->default(false);

            // Technical domains (60%)
            $table->decimal('security_score', 5, 2)->default(0);
            $table->decimal('compliance_score', 5, 2)->default(0);
            $table->decimal('architecture_score', 5, 2)->default(0);
            $table->decimal('infrastructure_score', 5, 2)->default(0);
            $table->decimal('data_engineering_score', 5, 2)->default(0);
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('api_score', 5, 2)->default(0);
            $table->decimal('testing_score', 5, 2)->default(0);
            $table->decimal('observability_score', 5, 2)->default(0);
            $table->decimal('communication_score', 5, 2)->default(0);

            // Autonomous Intelligence domains (30%)
            $table->decimal('ai_governance_score', 5, 2)->default(0);
            $table->decimal('ai_accuracy_score', 5, 2)->default(0);
            $table->decimal('ai_drift_score', 5, 2)->default(0);
            $table->decimal('agent_reliability_score', 5, 2)->default(0);
            $table->decimal('agent_collaboration_score', 5, 2)->default(0);
            $table->decimal('reality_alignment_score', 5, 2)->default(0);
            $table->decimal('hallucination_resistance_score', 5, 2)->default(0);
            $table->decimal('decision_intelligence_score', 5, 2)->default(0);

            // Business Intelligence domains (10%)
            $table->decimal('customer_success_score', 5, 2)->default(0);
            $table->decimal('operational_efficiency_score', 5, 2)->default(0);
            $table->decimal('financial_intelligence_score', 5, 2)->default(0);
            $table->decimal('product_strategy_score', 5, 2)->default(0);
            $table->decimal('innovation_score', 5, 2)->default(0);

            // Source scores (raw inputs)
            $table->decimal('data_trust_score', 5, 2)->default(0);
            $table->decimal('prediction_accuracy_score', 5, 2)->default(0);
            $table->decimal('org_memory_score', 5, 2)->default(0);

            // Gate status & full detail payload
            $table->json('gate_details')->nullable();
            $table->json('full_breakdown')->nullable();
            $table->json('recommendations')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        // Add MassPrunable-friendly index for retention (keep 12 months)
        Schema::table('platform_mega_scorecards', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_mega_scorecards');
    }
};

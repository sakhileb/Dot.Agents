<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Decision logs - every significant agent decision
        Schema::create('decision_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('agent_tasks')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('agent_sessions')->nullOnDelete();

            $table->string('decision_type'); // recommendation, action, analysis, communication, risk_flag
            $table->string('title');
            $table->text('decision_summary');
            $table->longText('reasoning')->nullable();
            $table->json('evidence_used')->nullable();
            $table->json('alternatives_considered')->nullable();
            $table->json('proposed_actions')->nullable();

            // Scoring
            $table->decimal('confidence_score', 5, 2);
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->decimal('impact_score', 5, 2)->default(0);

            // Delusion Detection
            $table->decimal('delusion_risk_score', 5, 2)->default(0);
            $table->decimal('reality_alignment_score', 5, 2)->default(100);
            $table->decimal('verification_score', 5, 2)->default(100);
            $table->decimal('evidence_quality_score', 5, 2)->default(100);
            $table->decimal('source_credibility_score', 5, 2)->default(100);
            $table->integer('assumption_count')->default(0);
            $table->boolean('contradicting_evidence')->default(false);
            $table->text('delusion_analysis')->nullable();

            // Compliance
            $table->boolean('compliance_checked')->default(false);
            $table->boolean('compliance_passed')->default(true);
            $table->json('compliance_notes')->nullable();

            // Outcome
            $table->boolean('requires_human_review')->default(false);
            $table->boolean('human_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('human_verdict')->nullable(); // approved, rejected, modified
            $table->text('human_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->string('final_outcome')->nullable(); // implemented, rejected, modified
            $table->text('outcome_notes')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agent_deployment_id', 'decision_type']);
            $table->index(['organization_id', 'requires_human_review']);
            $table->index(['delusion_risk_score']);
        });

        // Comprehensive audit trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('auditable'); // agent_deployment, user, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event'); // action performed
            $table->string('event_category'); // agent_action, user_action, system_event, security_event
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable();

            $table->string('risk_level')->default('low'); // low, medium, high, critical
            $table->boolean('flagged')->default(false);
            $table->string('flag_reason')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'event_category']);
            $table->index(['organization_id', 'flagged']);
            $table->index(['agent_deployment_id']);
            $table->index(['user_id']);
            $table->index(['created_at']);
        });

        // Agent scorecard metrics (calculated periodically)
        Schema::create('agent_scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('period'); // daily, weekly, monthly, quarterly
            $table->date('period_start');
            $table->date('period_end');

            // Individual scores (0-100)
            $table->decimal('accuracy_score', 5, 2)->default(0);
            $table->decimal('productivity_score', 5, 2)->default(0);
            $table->decimal('compliance_score', 5, 2)->default(0);
            $table->decimal('reliability_score', 5, 2)->default(0);
            $table->decimal('trustworthiness_score', 5, 2)->default(0);
            $table->decimal('cost_savings_score', 5, 2)->default(0);
            $table->decimal('revenue_impact_score', 5, 2)->default(0);
            $table->decimal('risk_impact_score', 5, 2)->default(0);
            $table->decimal('user_satisfaction_score', 5, 2)->default(0);
            $table->decimal('learning_rate_score', 5, 2)->default(0);

            // Overall health score (0-100)
            $table->decimal('overall_health_score', 5, 2)->default(0);

            // Raw metrics
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_failed')->default(0);
            $table->integer('decisions_made')->default(0);
            $table->integer('decisions_overridden')->default(0);
            $table->integer('hallucinations_detected')->default(0);
            $table->integer('approvals_requested')->default(0);
            $table->integer('approvals_granted')->default(0);
            $table->decimal('total_cost', 10, 4)->default(0);
            $table->decimal('estimated_savings', 10, 2)->default(0);
            $table->decimal('estimated_revenue_impact', 10, 2)->default(0);
            $table->integer('total_tokens_used')->default(0);
            $table->decimal('avg_response_time_ms', 10, 2)->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(100);

            $table->json('detailed_metrics')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();

            $table->unique(['agent_deployment_id', 'period', 'period_start']);
            $table->index(['organization_id', 'period']);
        });

        // Security events from Digital Immune System
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type'); // prompt_injection, permission_abuse, model_drift, agent_drift, data_breach_attempt, anomaly
            $table->string('severity'); // info, warning, error, critical
            $table->string('title');
            $table->text('description');
            $table->json('event_data')->nullable();
            $table->json('indicators')->nullable();
            $table->string('source_ip', 45)->nullable();

            $table->string('status')->default('open'); // open, investigating, resolved, false_positive
            $table->string('action_taken')->nullable(); // alert, quarantine, audit, escalate, recover
            $table->boolean('auto_remediated')->default(false);
            $table->text('remediation_notes')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'severity', 'status']);
            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('agent_scorecards');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('decision_logs');
    }
};

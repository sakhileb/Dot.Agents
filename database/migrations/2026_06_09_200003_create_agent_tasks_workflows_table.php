<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent Tasks - units of work assigned to agents
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('agent_sessions')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('agent_tasks')->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('task_type')->default('standard'); // standard, analysis, research, action, approval_required
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed, cancelled, awaiting_approval

            // Input/Output
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('result_summary')->nullable();
            $table->json('artifacts')->nullable(); // generated files, reports, etc.

            // Scoring
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->decimal('accuracy_score', 5, 2)->nullable();
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->decimal('delusion_risk_score', 5, 2)->nullable();
            $table->decimal('reality_alignment_score', 5, 2)->nullable();

            // Timing
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->integer('token_count')->default(0);
            $table->decimal('cost', 10, 4)->default(0);

            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_deployment_id', 'status']);
            $table->index(['organization_id', 'status', 'priority']);
        });

        // Agent decisions requiring human approval
        Schema::create('agent_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('task_id')->constrained('agent_tasks')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_from')->nullable()->constrained('users')->nullOnDelete();

            $table->string('approval_type'); // action, decision, expense, communication, data_access
            $table->string('title');
            $table->text('description');
            $table->json('proposed_action')->nullable();
            $table->json('impact_assessment')->nullable();
            $table->string('risk_level')->default('medium'); // low, medium, high, critical
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, escalated, expired

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->json('reviewer_data')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['requested_from', 'status']);
        });

        // Multi-agent workflow orchestration
        Schema::create('agent_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type')->default('manual'); // manual, scheduled, event, webhook
            $table->json('trigger_config')->nullable();
            $table->json('steps')->nullable(); // ordered list of agent steps
            $table->json('agents_involved')->nullable(); // agent_deployment_ids
            $table->string('status')->default('active'); // draft, active, paused, archived
            $table->boolean('is_template')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('agent_workflows')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('running'); // running, completed, failed, cancelled
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->json('step_results')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('current_step')->default(0);
            $table->integer('total_steps')->default(0);
            $table->decimal('total_cost', 10, 4)->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('agent_workflows');
        Schema::dropIfExists('agent_approvals');
        Schema::dropIfExists('agent_tasks');
    }
};

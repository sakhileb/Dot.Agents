<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend agent_skills with Enterprise Executable Skill fields ──────────

        Schema::table('agent_skills', function (Blueprint $table) {

            // Business classification
            $table->string('department')->nullable()->after('category');  // finance|hr|it|sales|marketing|operations|executive|platform
            $table->string('agent_type')->nullable()->after('department'); // which agent archetype owns this skill

            // Execution contract
            $table->string('output_type')->default('report')->after('requires_ai'); // report|action|notification|record|approval_request|data
            $table->string('risk_level')->default('low')->after('output_type');     // low|medium|high|critical

            // Approval & governance
            $table->boolean('approval_required')->default(false)->after('risk_level');
            $table->boolean('audit_required')->default(true)->after('approval_required');
            $table->boolean('delegation_capable')->default(false)->after('audit_required');

            // Permissions & data requirements (stored as JSON arrays)
            $table->json('required_permissions')->nullable()->after('manifest');   // ['finance.approve_invoice', ...]
            $table->json('required_data_sources')->nullable()->after('required_permissions'); // ['erp.invoices', 'crm.customers', ...]
            $table->json('governance_rules')->nullable()->after('required_data_sources');     // enforcement rules JSON

            // Scoring parameters
            $table->unsignedTinyInteger('confidence_score')->default(0)->after('governance_rules'); // 0-100 baseline confidence
            $table->string('sla_target')->nullable()->after('confidence_score');                    // e.g. '< 5 minutes'

            $table->index(['department', 'risk_level', 'is_active'], 'idx_skills_dept_risk');
            $table->index(['approval_required', 'risk_level'], 'idx_skills_approval_risk');
        });

        // ── 2. agent_skill_permissions — fine-grained permission registry ──────────

        Schema::create('agent_skill_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->string('permission_key');           // e.g. 'finance.approve_invoice'
            $table->string('permission_label');         // Human-readable label
            $table->string('scope')->default('execute'); // execute|read|delegate|approve
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);

            $table->timestamps();

            $table->unique(['skill_id', 'permission_key'], 'unique_skill_permission');
            $table->index(['permission_key', 'scope']);
        });

        // ── 3. agent_skill_requirements — data source / integration requirements ──

        Schema::create('agent_skill_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->string('requirement_type');         // data_source|integration|permission|config|model
            $table->string('requirement_key');          // e.g. 'erp.purchase_orders'
            $table->string('requirement_label');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('validation_config')->nullable(); // validation rules for the requirement

            $table->timestamps();

            $table->unique(['skill_id', 'requirement_key'], 'unique_skill_requirement');
        });

        // ── 4. agent_skill_approvals — per-execution approval tracking ────────────

        Schema::create('agent_skill_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();

            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->foreignId('execution_id')->nullable()->constrained('agent_skill_executions')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('pending');  // pending|approved|rejected|expired
            $table->string('risk_level');                  // inherited from skill at request time
            $table->json('context')->nullable();           // skill input context
            $table->text('justification')->nullable();     // reason for approval request
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at'], 'idx_skill_approvals_org_status');
            $table->index(['skill_id', 'status']);
            $table->index(['requested_by', 'status']);
        });

        // ── 5. agent_skill_audits — immutable execution audit trail ──────────────

        Schema::create('agent_skill_audits', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();

            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->foreignId('execution_id')->nullable()->constrained('agent_skill_executions')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('event_type');               // executed|blocked|approved|rejected|delegated|failed|skipped
            $table->string('outcome');                  // success|failure|blocked|pending_approval
            $table->json('policy_checks')->nullable();  // snapshot of all policy validation results
            $table->json('input_hash')->nullable();     // hashed input for integrity
            $table->json('metadata')->nullable();
            $table->text('reason')->nullable();         // reason for block/failure
            $table->decimal('confidence_at_execution', 5, 2)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'skill_id', 'occurred_at'], 'idx_skill_audits_org_skill');
            $table->index(['event_type', 'outcome', 'occurred_at']);
        });

        // ── 6. agent_skill_scores — per-deployment skill performance scores ────────

        Schema::create('agent_skill_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('skill_id')->constrained('agent_skills')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('period');                   // e.g. '2026-06' (YYYY-MM)

            // Execution statistics
            $table->unsignedInteger('total_executions')->default(0);
            $table->unsignedInteger('successful_executions')->default(0);
            $table->unsignedInteger('failed_executions')->default(0);
            $table->unsignedInteger('blocked_executions')->default(0);
            $table->unsignedInteger('approval_requests')->default(0);
            $table->unsignedInteger('approvals_granted')->default(0);
            $table->unsignedInteger('approvals_rejected')->default(0);

            // Quality scores (0-100)
            $table->decimal('accuracy_score', 5, 2)->nullable();
            $table->decimal('reliability_score', 5, 2)->nullable();
            $table->decimal('compliance_score', 5, 2)->nullable();
            $table->decimal('avg_confidence', 5, 2)->nullable();
            $table->decimal('avg_duration_ms', 10, 2)->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();      // computed: successful / total

            $table->timestamps();

            $table->unique(['skill_id', 'agent_deployment_id', 'period'], 'unique_skill_score_period');
            $table->index(['organization_id', 'period', 'accuracy_score'], 'idx_skill_scores_org_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_skill_scores');
        Schema::dropIfExists('agent_skill_audits');
        Schema::dropIfExists('agent_skill_approvals');
        Schema::dropIfExists('agent_skill_requirements');
        Schema::dropIfExists('agent_skill_permissions');

        Schema::table('agent_skills', function (Blueprint $table) {
            $table->dropIndex('idx_skills_dept_risk');
            $table->dropIndex('idx_skills_approval_risk');
            $table->dropColumn([
                'department',
                'agent_type',
                'output_type',
                'risk_level',
                'approval_required',
                'audit_required',
                'delegation_capable',
                'required_permissions',
                'required_data_sources',
                'governance_rules',
                'confidence_score',
                'sla_target',
            ]);
        });
    }
};

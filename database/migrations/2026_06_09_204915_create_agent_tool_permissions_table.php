<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent Tool Permissions
 *
 * Implements per-tool, per-role access control for AI agents.
 * Replaces the coarse deployment-level allowed_actions with a fine-grained
 * tool → role → agent permission matrix.
 *
 * Example:
 *   CEO Agent    → financial_reports, analytics (ALLOW)
 *   Developer    → deployments, repositories    (ALLOW)
 *   Support      → tickets                      (ALLOW)
 *   Any          → customer_pii                 (DENY)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tool_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // Null agent_deployment_id = org-wide rule (applies to all deployments)
            $table->foreignId('agent_deployment_id')->nullable()->constrained('agent_deployments')->cascadeOnDelete();
            // The tool / action / capability identifier (e.g. 'financial_reports', 'send_email')
            $table->string('tool_name');
            // allow | deny
            $table->enum('permission', ['allow', 'deny'])->default('allow');
            // Optional role scope (null = applies to all roles)
            $table->string('role_scope')->nullable()->comment('Spatie role name, or null for all roles');
            // Optional condition expression (JSON) for attribute-based access
            $table->json('conditions')->nullable()->comment('Attribute-based conditions, e.g. {"max_amount": 1000}');
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable()->comment('Human-readable justification for this rule');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'tool_name']);
            $table->index(['agent_deployment_id', 'tool_name']);
            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tool_permissions');
    }
};

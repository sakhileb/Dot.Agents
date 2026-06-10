<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes identified in the Full-Stack Integrity Audit.
     *
     * Covers high-traffic query patterns on:
     *  - agent_tasks        (time-range queries on dashboards / governance views)
     *  - security_events    (security dashboard: by org + created_at, by type+status)
     *  - decision_logs      (delusion monitoring: by deployment + date range)
     *  - audit_logs         (compliance queries: by org + event_category + date)
     */
    public function up(): void
    {
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index'");
        $existingIndexes = array_column($indexes, 'name');

        Schema::table('agent_tasks', function (Blueprint $table) use ($existingIndexes) {
            if (! in_array('agent_tasks_org_created_at_idx', $existingIndexes)) {
                $table->index(['organization_id', 'created_at'], 'agent_tasks_org_created_at_idx');
            }
        });

        Schema::table('security_events', function (Blueprint $table) use ($existingIndexes) {
            if (! in_array('security_events_org_created_at_idx', $existingIndexes)) {
                $table->index(['organization_id', 'created_at'], 'security_events_org_created_at_idx');
            }
            if (! in_array('security_events_org_type_status_idx', $existingIndexes)) {
                $table->index(['organization_id', 'event_type', 'status'], 'security_events_org_type_status_idx');
            }
        });

        Schema::table('decision_logs', function (Blueprint $table) use ($existingIndexes) {
            if (! in_array('decision_logs_org_created_at_idx', $existingIndexes)) {
                $table->index(['organization_id', 'created_at'], 'decision_logs_org_created_at_idx');
            }
            if (! in_array('decision_logs_deployment_created_at_idx', $existingIndexes)) {
                $table->index(['agent_deployment_id', 'created_at'], 'decision_logs_deployment_created_at_idx');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) use ($existingIndexes) {
            if (! in_array('audit_logs_org_created_at_idx', $existingIndexes)) {
                $table->index(['organization_id', 'created_at'], 'audit_logs_org_created_at_idx');
            }
            if (! in_array('audit_logs_org_event_idx', $existingIndexes)) {
                $table->index(['organization_id', 'event'], 'audit_logs_org_event_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            $table->dropIndexIfExists('agent_tasks_org_created_at_idx');
        });

        Schema::table('security_events', function (Blueprint $table) {
            $table->dropIndexIfExists('security_events_org_created_at_idx');
            $table->dropIndexIfExists('security_events_org_type_status_idx');
        });

        Schema::table('decision_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('decision_logs_org_created_at_idx');
            $table->dropIndexIfExists('decision_logs_deployment_created_at_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('audit_logs_org_created_at_idx');
            $table->dropIndexIfExists('audit_logs_org_event_idx');
        });
    }
};

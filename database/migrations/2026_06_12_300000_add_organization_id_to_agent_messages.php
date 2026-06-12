<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add organization_id to agent_messages for direct tenant isolation.
 *
 * Previously messages were only scoped via the agent_sessions relationship
 * (sessions have organization_id, messages did not). A direct AgentMessage::find()
 * or AgentMessage::where(…)->get() query bypassed all tenant guards.
 *
 * This migration:
 *  1. Adds a nullable organization_id column.
 *  2. Back-fills it from the parent agent_sessions row.
 *  3. Adds the foreign key + index.
 *
 * The model then applies HasOrganizationScope so every query is auto-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_messages', function (Blueprint $table) {
            // Nullable so the back-fill can run before the FK constraint is enforced
            $table->unsignedBigInteger('organization_id')->nullable()->after('session_id');
            $table->index(['organization_id', 'session_id'], 'agent_messages_org_session_idx');
        });

        // Back-fill: set organization_id from the parent agent_session
        DB::statement('
            UPDATE agent_messages
            SET organization_id = (
                SELECT organization_id
                FROM agent_sessions
                WHERE agent_sessions.id = agent_messages.session_id
            )
        ');

        // Now make it non-nullable + add FK (SQLite skips FK DDL but enforces at app level)
        Schema::table('agent_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_messages', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex('agent_messages_org_session_idx');
            $table->dropColumn('organization_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DWCA Remediation Migration
 *
 * Adds:
 *   1. decision_logs.input_hash         — SHA-256 of prompt input (PII-safe audit trail)
 *   2. decision_logs.model_used         — model name per decision (traceability)
 *   3. agents.maturity_level            — Agent Maturity Matrix level (0–10)
 *   4. agents.dwca_certified_at         — timestamp of last DWCA certification
 *   5. agents.dwca_certification_level  — DWCA enterprise certification level (1–6)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── decision_logs: add missing DWCA-required fields ──────────────────
        Schema::table('decision_logs', function (Blueprint $table) {
            // SHA-256 hash of the task prompt — NEVER store raw PII in logs
            $table->string('input_hash', 64)->nullable()->after('task_id')
                ->comment('SHA-256 of the raw task prompt. Used for audit deduplication without storing PII.');

            // Which AI model produced this decision
            $table->string('model_used', 100)->nullable()->after('input_hash')
                ->comment('AI model used to produce this decision (e.g. gpt-4o, claude-3-5-sonnet).');

            $table->index('input_hash');
        });

        // ── agents: Agent Maturity Matrix + DWCA certification ───────────────
        Schema::table('agents', function (Blueprint $table) {
            // Agent Maturity Matrix level: 0–10
            // 0=Registered, 1=Skills Defined, 2=Skills Executable, 3=Governed,
            // 4=Multi-agent Capable, 5=Autonomous, 6=Enterprise Certified,
            // 7=Self-Optimizing, 8=Digital Department, 9=Digital Executive, 10=Autonomous Business Unit
            $table->unsignedTinyInteger('maturity_level')->default(0)->after('trust_tier')
                ->comment('Agent Maturity Matrix level 0–10. Must be ≥6 for marketplace deployment.');

            // DWCA certification tracking
            $table->timestamp('dwca_certified_at')->nullable()->after('maturity_level');
            $table->unsignedTinyInteger('dwca_certification_level')->default(0)->after('dwca_certified_at')
                ->comment('DWCA certification level: 1=Experimental, 2=Internal, 3=Production Ready, 4=Enterprise Ready, 5=Enterprise Certified, 6=World Class Digital Workforce');

            $table->index('maturity_level');
            $table->index('dwca_certification_level');
        });
    }

    public function down(): void
    {
        Schema::table('decision_logs', function (Blueprint $table) {
            $table->dropIndex(['input_hash']);
            $table->dropColumn(['input_hash', 'model_used']);
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex(['maturity_level']);
            $table->dropIndex(['dwca_certification_level']);
            $table->dropColumn(['maturity_level', 'dwca_certified_at', 'dwca_certification_level']);
        });
    }
};

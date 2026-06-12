<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add MySQL RANGE partitioning to audit_logs by created_at (monthly buckets)
 * and register the model:prune retention policy (2-year rolling window).
 *
 * Pruning is handled by the AuditLog model's `prunable()` scope via the
 * Laravel model:prune Artisan command (scheduled daily in the Console Kernel).
 *
 * This migration is a no-op on SQLite (used in testing).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add pruning index on created_at for efficient DELETE during prune runs.
        // Wrapped in a try/catch so it's safe to run even if the index already exists.
        try {
            Schema::table('audit_logs', function ($table) {
                $table->index('created_at', 'audit_logs_created_at_index');
            });
        } catch (Exception $e) {
            // Index already exists — acceptable in re-run scenarios
        }

        // Partitioning is MySQL-only
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE audit_logs
            PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
                PARTITION p_before_2025 VALUES LESS THAN (202501),
                PARTITION p_2025_01    VALUES LESS THAN (202502),
                PARTITION p_2025_02    VALUES LESS THAN (202503),
                PARTITION p_2025_03    VALUES LESS THAN (202504),
                PARTITION p_2025_04    VALUES LESS THAN (202505),
                PARTITION p_2025_05    VALUES LESS THAN (202506),
                PARTITION p_2025_06    VALUES LESS THAN (202507),
                PARTITION p_2025_07    VALUES LESS THAN (202508),
                PARTITION p_2025_08    VALUES LESS THAN (202509),
                PARTITION p_2025_09    VALUES LESS THAN (202510),
                PARTITION p_2025_10    VALUES LESS THAN (202511),
                PARTITION p_2025_11    VALUES LESS THAN (202512),
                PARTITION p_2025_12    VALUES LESS THAN (202601),
                PARTITION p_future     VALUES LESS THAN MAXVALUE
            )
        SQL);
    }

    public function down(): void
    {
        try {
            Schema::table('audit_logs', function ($table) {
                $table->dropIndex('audit_logs_created_at_index');
            });
        } catch (Exception $e) {
            // Index did not exist
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE audit_logs REMOVE PARTITIONING');
    }
};

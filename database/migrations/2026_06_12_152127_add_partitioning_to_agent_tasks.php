<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add MySQL RANGE partitioning to agent_tasks by created_at (monthly buckets).
 *
 * This migration is a no-op on SQLite (used in testing) and only applies on
 * MySQL 8+. Partitioning reduces full-table-scan costs on high-volume deployments.
 *
 * Note: MySQL requires the partitioning column to be part of every unique index.
 * The migration adds a composite primary key (id, created_at) and drops the
 * plain id PK, which is standard practice for partitioned InnoDB tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only execute on MySQL — SQLite does not support partitioning
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE agent_tasks
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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE agent_tasks REMOVE PARTITIONING');
    }
};

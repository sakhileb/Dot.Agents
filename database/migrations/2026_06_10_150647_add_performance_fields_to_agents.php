<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Performance score (0–100): aggregated from task accuracy, reliability, satisfaction
            $table->decimal('performance_score', 5, 2)->default(75.00)->after('satisfaction_score');

            // Competency map: array of {area, level} objects e.g. [{area:"Financial Analysis",level:"expert"}]
            $table->json('competencies')->nullable()->after('skills');

            // Cost tier: derived from base_price for fast filtering
            // free (0), low (1-99), medium (100-249), high (250+)
            $table->string('cost_tier', 10)->default('low')->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['performance_score', 'competencies', 'cost_tier']);
        });
    }
};

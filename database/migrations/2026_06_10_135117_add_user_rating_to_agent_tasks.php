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
        Schema::table('agent_tasks', function (Blueprint $table) {
            // User satisfaction rating: 1–5 stars (nullable — not all tasks are rated)
            $table->tinyInteger('user_rating')->unsigned()->nullable()->after('accuracy_score')
                ->comment('User satisfaction rating 1–5 stars');
            $table->text('user_feedback')->nullable()->after('user_rating')
                ->comment('Optional freeform user feedback');
            $table->timestamp('rated_at')->nullable()->after('user_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            $table->dropColumn(['user_rating', 'user_feedback', 'rated_at']);
        });
    }
};

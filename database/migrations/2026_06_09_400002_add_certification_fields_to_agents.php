<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->unsignedTinyInteger('certification_score')->default(0)->after('review_count')
                ->comment('0-100 weighted certification score from AgentCertificationService');
            $table->unsignedTinyInteger('trust_score')->default(0)->after('certification_score')
                ->comment('0-100 trust score derived from live task data');
            $table->string('trust_tier')->default('uncertified')->after('trust_score')
                ->comment('platinum|gold|silver|bronze|uncertified');
            $table->timestamp('certified_at')->nullable()->after('trust_tier')
                ->comment('When the agent last received a certification score');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['certification_score', 'trust_score', 'trust_tier', 'certified_at']);
        });
    }
};

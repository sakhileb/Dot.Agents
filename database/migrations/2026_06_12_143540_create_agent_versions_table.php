<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('version', 20);          // e.g. '1.0.0', '2.3.1'
            $table->string('status')->default('draft'); // draft|published|deprecated
            $table->text('release_notes')->nullable();
            $table->json('config_snapshot');         // Full agent config at this version
            $table->json('capabilities_snapshot');   // Skills / tools / persona at publish
            $table->boolean('is_current')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'version']);
            $table->index(['agent_id', 'is_current']);
            $table->index(['agent_id', 'status']);
        });

        // Add rollback support to deployments — track which version they're running
        Schema::table('agent_deployments', function (Blueprint $table) {
            $table->foreignId('agent_version_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('agent_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_deployments', function (Blueprint $table) {
            $table->dropForeign(['agent_version_id']);
            $table->dropColumn('agent_version_id');
        });

        Schema::dropIfExists('agent_versions');
    }
};

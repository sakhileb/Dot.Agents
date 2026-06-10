<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            // Add columns expected by the KnowledgeBase model and SaveKnowledgeBaseAction
            // that were missing from the original migration.
            if (! Schema::hasColumn('knowledge_bases', 'access_level')) {
                $table->string('access_level')->default('internal')->after('type');
                // access_level values: internal, restricted, public
            }

            if (! Schema::hasColumn('knowledge_bases', 'settings')) {
                $table->json('settings')->nullable()->after('access_level');
            }

            if (! Schema::hasColumn('knowledge_bases', 'metadata')) {
                $table->json('metadata')->nullable()->after('settings');
            }

            // Make slug and created_by nullable so inserts that don't provide them don't fail.
            if (Schema::hasColumn('knowledge_bases', 'slug')) {
                $table->string('slug')->nullable()->change();
            }

            if (Schema::hasColumn('knowledge_bases', 'created_by')) {
                $table->foreignId('created_by')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('knowledge_bases', 'access_level') ? 'access_level' : null,
                Schema::hasColumn('knowledge_bases', 'settings') ? 'settings' : null,
                Schema::hasColumn('knowledge_bases', 'metadata') ? 'metadata' : null,
            ]));
        });
    }
};

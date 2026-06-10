<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            // Add organization_id — required by HasOrganizationScope for tenant isolation.
            // Nullable first so the backfill can run before enforcing NOT NULL.
            $table->foreignId('organization_id')
                ->nullable()
                ->after('knowledge_base_id')
                ->constrained()
                ->cascadeOnDelete();

            // Add author_id — alias for created_by used by the KnowledgeArticle model.
            // Only add if it does not already exist (safe re-run).
            if (! Schema::hasColumn('knowledge_articles', 'author_id')) {
                $table->foreignId('author_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // Backfill organization_id from the parent knowledge_bases table
        DB::statement('
            UPDATE knowledge_articles
            SET organization_id = (
                SELECT organization_id
                FROM knowledge_bases
                WHERE knowledge_bases.id = knowledge_articles.knowledge_base_id
            )
            WHERE organization_id IS NULL
        ');

        // Backfill author_id from created_by where author_id is still null
        DB::statement('
            UPDATE knowledge_articles
            SET author_id = created_by
            WHERE author_id IS NULL AND created_by IS NOT NULL
        ');

        // Now add the index for query performance
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->index(['organization_id', 'is_published'], 'ka_org_published_idx');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropIndex('ka_org_published_idx');
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');

            if (Schema::hasColumn('knowledge_articles', 'author_id')) {
                $table->dropForeign(['author_id']);
                $table->dropColumn('author_id');
            }
        });
    }
};

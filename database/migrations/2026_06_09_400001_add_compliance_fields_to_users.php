<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'consent_records')) {
                $table->json('consent_records')->nullable()->after('remember_token')
                    ->comment('GDPR/POPIA consent log keyed by purpose');
            }
            if (! Schema::hasColumn('users', 'erased_at')) {
                $table->timestamp('erased_at')->nullable()->after('consent_records')
                    ->comment('Set when user exercises GDPR right to erasure');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['consent_records', 'erased_at']);
        });
    }
};

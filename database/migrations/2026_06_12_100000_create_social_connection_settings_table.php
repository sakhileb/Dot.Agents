<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_connection_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('platform', 50);
            $table->json('goals')->nullable();           // e.g. ['generate_leads', 'provide_support']
            $table->json('ai_features')->nullable();    // e.g. ['customer_support', 'lead_generation']
            $table->json('permissions')->nullable();    // e.g. ['reply_comments', 'reply_messages']
            $table->unsignedTinyInteger('autonomy_level')->default(1); // 0=observe … 4=sales
            $table->string('status', 20)->default('active'); // active | paused
            $table->timestamps();

            $table->index(['organization_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_connection_settings');
    }
};

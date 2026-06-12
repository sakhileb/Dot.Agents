<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_social_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 50); // facebook | instagram | linkedin | twitter | tiktok
            $table->text('client_id');      // encrypted
            $table->text('client_secret');  // encrypted
            $table->string('redirect_uri')->nullable(); // override, defaults to app route
            $table->json('extra_config')->nullable();   // platform-specific extras (e.g. scopes)
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_social_credentials');
    }
};

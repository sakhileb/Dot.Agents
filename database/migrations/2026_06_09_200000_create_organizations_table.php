<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('logo')->nullable();
            $table->string('industry')->nullable();
            $table->string('size')->nullable(); // startup, smb, mid-market, enterprise
            $table->string('country')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->string('plan')->default('starter'); // starter, professional, enterprise
            $table->string('status')->default('active'); // active, suspended, trial
            $table->json('settings')->nullable();
            $table->json('billing_address')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, admin, manager, member, viewer
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('operational'); // executive, operational, support
            $table->text('description')->nullable();
            $table->string('head_name')->nullable();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('budget')->nullable();
            $table->string('cost_center')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};

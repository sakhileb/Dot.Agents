<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription plans
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->decimal('price', 10, 2);
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->integer('max_agents')->default(5);
            $table->integer('max_users')->default(10);
            $table->integer('max_departments')->default(3);
            $table->integer('max_workflows')->default(5);
            $table->integer('monthly_token_quota')->default(1000000);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Organization subscriptions
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->string('status'); // active, trialing, past_due, cancelled, paused
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // Usage tracking for billing
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric_type'); // tokens, tasks, api_calls, storage_gb
            $table->bigInteger('quantity');
            $table->decimal('unit_cost', 10, 8)->default(0);
            $table->decimal('total_cost', 10, 4)->default(0);
            $table->string('model_used')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('recorded_date');
            $table->timestamps();

            $table->index(['organization_id', 'metric_type', 'recorded_date']);
            $table->index(['agent_deployment_id', 'recorded_date']);
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('status'); // draft, open, paid, uncollectible, void
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('line_items')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('billing_details')->nullable();
            $table->timestamps();
        });

        // Marketplace reviews
        Schema::create('agent_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_id')->nullable()->constrained('agent_deployments')->nullOnDelete();
            $table->integer('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('dimension_scores')->nullable(); // accuracy, speed, reliability, etc.
            $table->boolean('is_verified')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agent_id', 'organization_id', 'user_id']);
            $table->index(['agent_id', 'rating']);
        });

        // Notifications
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // alert, approval_request, task_complete, system, billing, security
            $table->string('channel')->default('platform'); // platform, email, sms, webhook
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('agent_reviews');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};

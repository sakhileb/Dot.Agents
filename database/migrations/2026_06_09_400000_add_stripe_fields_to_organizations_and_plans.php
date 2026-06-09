<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->unique()->after('owner_id');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('subscription_plans', 'stripe_product_id')) {
                $table->string('stripe_product_id')->nullable()->after('stripe_price_id');
            }
            if (! Schema::hasColumn('subscription_plans', 'trial_days')) {
                $table->unsignedTinyInteger('trial_days')->default(0)->after('stripe_product_id');
            }
            if (! Schema::hasColumn('subscription_plans', 'billing_cycle')) {
                $table->string('billing_cycle')->default('monthly')->after('trial_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id', 'stripe_product_id', 'trial_days', 'billing_cycle']);
        });
    }
};

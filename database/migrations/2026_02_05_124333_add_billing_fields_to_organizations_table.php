<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->index()->after('owner_user_id');
            $table->string('stripe_subscription_id')->nullable()->index()->after('stripe_customer_id');
            $table->string('plan_key')->default('free')->index()->after('stripe_subscription_id');
            $table->enum('plan_status', ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'unpaid'])->default('active')->after('plan_key');
            $table->timestamp('trial_ends_at')->nullable()->after('plan_status');
            $table->integer('seats_limit')->default(1)->after('trial_ends_at');
            $table->integer('seats_used')->default(1)->after('seats_limit');
            $table->string('billing_email')->nullable()->after('seats_used');
            $table->string('billing_name')->nullable()->after('billing_email');
            $table->json('billing_address')->nullable()->after('billing_name');
            $table->timestamp('usage_period_started_at')->nullable()->after('billing_address');
            $table->timestamp('usage_period_ends_at')->nullable()->after('usage_period_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'plan_key',
                'plan_status',
                'trial_ends_at',
                'seats_limit',
                'seats_used',
                'billing_email',
                'billing_name',
                'billing_address',
                'usage_period_started_at',
                'usage_period_ends_at',
            ]);
        });
    }
};

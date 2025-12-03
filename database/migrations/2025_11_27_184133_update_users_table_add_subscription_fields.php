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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('email')->constrained('plans')->onDelete('set null');
            $table->string('stripe_customer_id')->nullable()->after('plan_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->enum('subscription_status', ['active', 'canceled', 'past_due', 'trialing', 'incomplete'])->nullable()->after('stripe_subscription_id');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
                'trial_ends_at',
            ]);
        });
    }
};

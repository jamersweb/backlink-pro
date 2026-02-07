<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->integer('amount_cents');
            $table->string('currency', 10)->default('usd');
            $table->decimal('commission_rate', 5, 2);
            $table->enum('type', ['subscription_first', 'subscription_recurring', 'one_time']);
            $table->enum('status', ['pending', 'approved', 'paid', 'reversed'])->default('pending');
            $table->timestamp('eligible_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status', 'eligible_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};

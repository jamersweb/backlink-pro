<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('referred_org_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->string('visitor_id', 64)->nullable();
            $table->timestamp('first_touch_at')->nullable();
            $table->timestamp('last_touch_at')->nullable();
            $table->json('utm')->nullable();
            $table->string('landing_page')->nullable();
            $table->string('referrer_url')->nullable();
            $table->enum('status', ['clicked', 'signed_up', 'trial_started', 'converted', 'canceled'])->default('clicked');
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};

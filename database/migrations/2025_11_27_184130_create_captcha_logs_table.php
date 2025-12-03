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
        Schema::create('captcha_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('site_domain');
            $table->enum('captcha_type', ['image', 'recaptcha_v2', 'recaptcha_invisible', 'hcaptcha']);
            $table->enum('service', ['2captcha', 'anticaptcha'])->default('2captcha');
            $table->string('order_id')->nullable();
            $table->enum('status', ['pending', 'solved', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->decimal('estimated_cost', 8, 4)->default(0);
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captcha_logs');
    }
};

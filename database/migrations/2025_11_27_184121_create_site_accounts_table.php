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
        Schema::create('site_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('site_domain');
            $table->string('login_email');
            $table->string('username')->nullable();
            $table->string('password')->nullable(); // Encrypted if stored
            $table->enum('status', ['created', 'waiting_email', 'verified', 'failed'])->default('created');
            $table->text('verification_link')->nullable();
            $table->enum('email_verification_status', ['pending', 'found', 'timeout'])->default('pending');
            $table->timestamp('last_verification_check_at')->nullable();
            $table->timestamps();
            
            // Prevent duplicate accounts for same site
            $table->unique(['campaign_id', 'site_domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_accounts');
    }
};

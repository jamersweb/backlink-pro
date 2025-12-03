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
        Schema::create('automation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['comment', 'profile', 'forum', 'guest', 'email_confirmation_click']);
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'cancelled'])->default('pending');
            $table->json('payload')->nullable(); // Task-specific data
            $table->json('result')->nullable(); // Task result data
            $table->text('error_message')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('locked_by')->nullable(); // Worker ID
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamps();
            
            // Index for faster pending task queries
            $table->index(['status', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_tasks');
    }
};

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
        Schema::create('automation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('automation_campaigns')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('automation_targets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->enum('action', ['comment', 'profile', 'forum', 'guest'])->index();
            $table->enum('status', ['queued', 'locked', 'running', 'success', 'failed', 'retrying', 'skipped'])->default('queued')->index();
            $table->unsignedSmallInteger('priority')->default(5);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(2);
            $table->char('lock_token', 40)->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('result_json')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'created_at']);
            $table->index(['campaign_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_jobs');
    }
};

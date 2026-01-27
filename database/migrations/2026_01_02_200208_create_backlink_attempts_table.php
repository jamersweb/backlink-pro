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
        Schema::create('backlink_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('automation_campaigns')->onDelete('set null');
            $table->foreignId('job_id')->nullable()->constrained('automation_jobs')->onDelete('set null');
            $table->text('target_url');
            $table->string('target_domain')->index();
            $table->string('detected_platform')->nullable();
            $table->enum('action_attempted', ['comment', 'profile', 'forum', 'guest']);
            $table->enum('result', ['success', 'failed', 'skipped'])->index();
            $table->string('failure_reason')->nullable();
            $table->text('created_backlink_url')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at');

            $table->index(['target_domain', 'created_at']);
            $table->index(['result', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_attempts');
    }
};

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
        Schema::create('automation_job_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('automation_jobs')->onDelete('cascade');
            $table->enum('level', ['info', 'warning', 'error', 'debug']);
            $table->text('message');
            $table->json('context_json')->nullable();
            $table->timestamp('created_at');

            $table->index(['job_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_job_logs');
    }
};

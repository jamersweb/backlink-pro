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
        Schema::create('job_failures', function (Blueprint $table) {
            $table->id();
            $table->string('feature')->index();
            $table->string('job_name');
            $table->foreignId('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('run_ref')->nullable()->index(); // "audit:123", "backlinks:55"
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->timestamp('failed_at');
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->index('failed_at');
            $table->index(['feature', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_failures');
    }
};

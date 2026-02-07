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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->enum('mode', ['guest', 'auth'])->default('guest');
            $table->string('lead_email')->nullable();
            $table->integer('overall_score')->nullable();
            $table->string('overall_grade', 2)->nullable();
            $table->json('category_scores')->nullable(); // onpage, technical, performance, links, usability, social, local, security
            $table->json('summary')->nullable(); // top KPIs
            $table->string('share_token', 64)->nullable()->unique();
            $table->boolean('is_public')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('user_id');
            $table->index('share_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};

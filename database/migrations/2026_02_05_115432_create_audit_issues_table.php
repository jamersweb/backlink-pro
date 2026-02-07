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
        Schema::create('audit_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('code', 80);
            $table->string('title', 255);
            $table->text('description');
            $table->enum('impact', ['high', 'medium', 'low']);
            $table->enum('effort', ['easy', 'medium', 'hard']);
            $table->integer('score_penalty')->default(0);
            $table->integer('affected_count')->default(1);
            $table->text('recommendation')->nullable();
            $table->json('fix_steps')->nullable(); // steps + snippets
            $table->timestamps();

            // Indexes
            $table->index('audit_id');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_issues');
    }
};

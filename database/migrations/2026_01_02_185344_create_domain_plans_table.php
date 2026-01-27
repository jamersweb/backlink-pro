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
        Schema::create('domain_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'applied', 'archived'])->default('draft')->index();
            $table->unsignedSmallInteger('period_days')->default(28);
            $table->json('plan_json');
            $table->enum('generated_by', ['heuristic', 'llm'])->default('heuristic');
            $table->timestamp('generated_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_plans');
    }
};

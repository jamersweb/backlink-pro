<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_research_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('input_type');
            $table->text('seed_query')->nullable();
            $table->string('seed_url')->nullable();
            $table->text('context_text')->nullable();
            $table->string('locale_country')->nullable();
            $table->string('locale_language')->nullable();
            $table->string('status')->default('completed');
            $table->text('summary_text')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_research_runs');
    }
};

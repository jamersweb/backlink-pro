<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_research_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('keyword_research_runs')->cascadeOnDelete();
            $table->string('keyword');
            $table->string('normalized_keyword');
            $table->string('source')->default('ai');
            $table->string('intent')->nullable();
            $table->string('funnel_stage')->nullable();
            $table->string('cluster_name')->nullable();
            $table->string('recommended_content_type')->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->unsignedTinyInteger('business_relevance_score')->nullable();
            $table->text('ai_reason')->nullable();
            $table->boolean('is_saved')->default(false);
            $table->timestamps();

            $table->index('run_id');
            $table->index('normalized_keyword');
            $table->unique(['run_id', 'normalized_keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_research_items');
    }
};

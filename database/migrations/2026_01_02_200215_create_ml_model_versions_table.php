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
        Schema::create('ml_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "action_selector"
            $table->string('version'); // "2026-01-03-001"
            $table->string('artifact_path'); // where model.pkl saved
            $table->json('metrics_json'); // accuracy, f1, confusion matrix
            $table->unsignedInteger('trained_on_rows');
            $table->char('feature_schema_hash', 64);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_model_versions');
    }
};

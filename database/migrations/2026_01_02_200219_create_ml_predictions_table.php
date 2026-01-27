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
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_version_id')->nullable()->constrained('ml_model_versions')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('domain_id')->nullable()->constrained('domains')->onDelete('set null');
            $table->text('target_url');
            $table->char('features_hash', 64)->index();
            $table->string('predicted_action');
            $table->decimal('confidence', 5, 4);
            $table->json('probabilities_json');
            $table->timestamp('created_at');

            $table->index(['model_version_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};

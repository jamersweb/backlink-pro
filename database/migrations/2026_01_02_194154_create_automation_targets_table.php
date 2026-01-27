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
        Schema::create('automation_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('automation_campaigns')->onDelete('cascade');
            $table->text('url');
            $table->char('url_hash', 64)->index();
            $table->enum('source', ['manual', 'csv', 'backlinks_run', 'insights'])->index();
            $table->text('anchor_text')->nullable();
            $table->text('target_link')->nullable();
            $table->string('keyword')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at');

            $table->unique(['campaign_id', 'url_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_targets');
    }
};

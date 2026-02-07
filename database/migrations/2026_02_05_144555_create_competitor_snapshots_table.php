<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_run_id')->constrained('competitor_runs')->onDelete('cascade');
            $table->string('keyword');
            $table->string('competitor_url', 2048);
            $table->string('domain');
            $table->text('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('word_count')->nullable();
            $table->json('lighthouse_mobile')->nullable();
            $table->json('lighthouse_desktop')->nullable();
            $table->bigInteger('page_weight_bytes')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();

            $table->index(['competitor_run_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_snapshots');
    }
};

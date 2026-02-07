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
        Schema::create('audit_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('url', 2048);
            $table->integer('status_code')->nullable();
            $table->text('title')->nullable();
            $table->integer('title_len')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('meta_len')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_meta')->nullable();
            $table->integer('h1_count')->default(0);
            $table->integer('h2_count')->default(0);
            $table->integer('h3_count')->default(0);
            $table->integer('word_count')->default(0);
            $table->integer('images_total')->default(0);
            $table->integer('images_missing_alt')->default(0);
            $table->integer('internal_links_count')->default(0);
            $table->integer('external_links_count')->default(0);
            $table->boolean('og_present')->default(false);
            $table->boolean('twitter_cards_present')->default(false);
            $table->json('schema_types')->nullable();
            $table->bigInteger('html_size_bytes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('audit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_pages');
    }
};

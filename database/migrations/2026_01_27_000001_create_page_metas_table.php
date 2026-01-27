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
        Schema::create('page_metas', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // Unique identifier for the page (e.g., 'home', 'pricing', 'about')
            $table->string('page_name'); // Human-readable name
            $table->string('route_name')->nullable(); // Laravel route name
            $table->string('url_path'); // URL path (e.g., '/', '/pricing')
            
            // SEO Meta Tags
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Open Graph
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_type')->default('website');
            
            // Twitter Card
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            
            // Schema.org JSON-LD
            $table->json('schema_json')->nullable();
            
            // Page Content (for dynamic content sections)
            $table->json('content_json')->nullable();
            
            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_indexable')->default(true); // robots index/noindex
            $table->boolean('is_followable')->default(true); // robots follow/nofollow
            $table->string('canonical_url')->nullable();
            
            // Tracking
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metas');
    }
};

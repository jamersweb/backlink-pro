<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('index_crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('total_urls_discovered')->default(0);
            $table->unsignedInteger('total_urls_crawled')->default(0);
            $table->unsignedInteger('total_issues')->default(0);
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('settings_json')->nullable();
            $table->json('summary_json')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('index_crawl_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('index_crawl_run_id')->constrained('index_crawl_runs')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->string('normalized_url_hash', 64);
            $table->string('source_found_from', 50)->default('crawl');
            $table->string('final_url', 2048)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('content_type', 255)->nullable();
            $table->boolean('is_html')->default(false);
            $table->string('title', 500)->nullable();
            $table->string('meta_description', 1000)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('meta_robots', 500)->nullable();
            $table->string('x_robots_tag', 500)->nullable();
            $table->boolean('is_blocked_by_robots')->default(false);
            $table->boolean('is_noindex')->default(false);
            $table->boolean('is_in_sitemap')->default(false);
            $table->unsignedSmallInteger('click_depth')->nullable();
            $table->unsignedInteger('internal_inlinks_count')->default(0);
            $table->unsignedInteger('internal_outlinks_count')->default(0);
            $table->string('crawlability_status', 80)->nullable();
            $table->string('indexability_status', 120)->nullable();
            $table->json('issue_flags_json')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['index_crawl_run_id', 'normalized_url_hash'], 'idx_crawl_urls_run_norm_hash_unique');
            $table->index(['domain_id', 'created_at']);
            $table->index(['index_crawl_run_id', 'status_code']);
            $table->index(['index_crawl_run_id', 'crawlability_status']);
            $table->index(['index_crawl_run_id', 'indexability_status']);
            $table->index(['index_crawl_run_id', 'is_in_sitemap']);
            $table->index(['index_crawl_run_id', 'is_noindex']);
            $table->index(['index_crawl_run_id', 'is_blocked_by_robots']);
        });

        Schema::create('index_crawl_sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('index_crawl_run_id')->constrained('index_crawl_runs')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('sitemap_url', 2048);
            $table->string('type', 40)->default('sitemap');
            $table->string('fetch_status', 40)->default('pending');
            $table->unsignedInteger('total_urls_found')->default(0);
            $table->unsignedInteger('valid_urls_found')->default(0);
            $table->unsignedTinyInteger('health_score')->nullable();
            $table->json('issues_json')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['index_crawl_run_id', 'created_at']);
            $table->index(['domain_id', 'created_at']);
        });

        Schema::create('index_crawl_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('index_crawl_run_id')->constrained('index_crawl_runs')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('issue_key', 120);
            $table->string('issue_name', 190);
            $table->string('severity', 30);
            $table->unsignedInteger('affected_urls_count')->default(0);
            $table->string('description', 1000)->nullable();
            $table->string('recommendation', 1000)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['index_crawl_run_id', 'severity']);
            $table->index(['index_crawl_run_id', 'issue_key']);
            $table->index(['domain_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_crawl_issues');
        Schema::dropIfExists('index_crawl_sitemaps');
        Schema::dropIfExists('index_crawl_urls');
        Schema::dropIfExists('index_crawl_runs');
    }
};

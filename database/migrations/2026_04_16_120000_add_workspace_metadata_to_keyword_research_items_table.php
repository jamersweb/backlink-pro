<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('keyword_research_items')) {
            return;
        }

        Schema::table('keyword_research_items', function (Blueprint $table) {
            if (!Schema::hasColumn('keyword_research_items', 'pattern_type')) {
                $table->string('pattern_type')->nullable()->after('source');
            }
            if (!Schema::hasColumn('keyword_research_items', 'generation_meta_json')) {
                $table->json('generation_meta_json')->nullable()->after('ai_reason');
            }
            if (!Schema::hasColumn('keyword_research_items', 'search_volume')) {
                $table->unsignedInteger('search_volume')->nullable()->after('keyword_traffic');
            }
            if (!Schema::hasColumn('keyword_research_items', 'metrics_provider')) {
                $table->string('metrics_provider')->nullable()->after('search_volume');
            }
            if (!Schema::hasColumn('keyword_research_items', 'metrics_status')) {
                $table->string('metrics_status')->default('pending')->after('metrics_provider');
            }
            if (!Schema::hasColumn('keyword_research_items', 'metrics_error')) {
                $table->text('metrics_error')->nullable()->after('metrics_status');
            }
            if (!Schema::hasColumn('keyword_research_items', 'competition_score')) {
                $table->unsignedTinyInteger('competition_score')->nullable()->after('metrics_error');
            }
            if (!Schema::hasColumn('keyword_research_items', 'cpc_value')) {
                $table->decimal('cpc_value', 10, 2)->nullable()->after('competition_score');
            }
            if (!Schema::hasColumn('keyword_research_items', 'trend_json')) {
                $table->json('trend_json')->nullable()->after('cpc_value');
            }
            if (!Schema::hasColumn('keyword_research_items', 'provider_response_json')) {
                $table->json('provider_response_json')->nullable()->after('trend_json');
            }
            if (!Schema::hasColumn('keyword_research_items', 'last_enriched_at')) {
                $table->timestamp('last_enriched_at')->nullable()->after('provider_response_json');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_status')) {
                $table->string('density_status')->default('not_analyzed')->after('keyword_density_pct');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_target_url')) {
                $table->string('density_target_url')->nullable()->after('density_status');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_total_words')) {
                $table->unsignedInteger('density_total_words')->nullable()->after('density_target_url');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_exact_match_count')) {
                $table->unsignedInteger('density_exact_match_count')->nullable()->after('density_total_words');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_partial_match_count')) {
                $table->unsignedInteger('density_partial_match_count')->nullable()->after('density_exact_match_count');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_error')) {
                $table->text('density_error')->nullable()->after('density_partial_match_count');
            }
            if (!Schema::hasColumn('keyword_research_items', 'density_analyzed_at')) {
                $table->timestamp('density_analyzed_at')->nullable()->after('density_error');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('keyword_research_items')) {
            return;
        }

        Schema::table('keyword_research_items', function (Blueprint $table) {
            foreach ([
                'pattern_type',
                'generation_meta_json',
                'search_volume',
                'metrics_provider',
                'metrics_status',
                'metrics_error',
                'competition_score',
                'cpc_value',
                'trend_json',
                'provider_response_json',
                'last_enriched_at',
                'density_status',
                'density_target_url',
                'density_total_words',
                'density_exact_match_count',
                'density_partial_match_count',
                'density_error',
                'density_analyzed_at',
            ] as $column) {
                if (Schema::hasColumn('keyword_research_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

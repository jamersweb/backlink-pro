<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: rank_checks table exists but has different structure (batch runs)
        // rank_results table stores individual keyword positions
        // For Phase 9, we'll use rank_results which already has position/found_url
        // This migration is kept for future use if needed
        
        // Just ensure rank_results has the columns we need
        if (Schema::hasTable('rank_results')) {
            if (!Schema::hasColumn('rank_results', 'serp_features')) {
                Schema::table('rank_results', function (Blueprint $table) {
                    $table->json('serp_features')->nullable()->after('features_json');
                });
            }
            if (!Schema::hasColumn('rank_results', 'source')) {
                Schema::table('rank_results', function (Blueprint $table) {
                    $table->enum('source', ['serp_api', 'manual'])->default('serp_api')->after('serp_features');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_checks');
    }
};

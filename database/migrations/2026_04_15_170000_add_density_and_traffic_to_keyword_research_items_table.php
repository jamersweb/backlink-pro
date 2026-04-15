<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keyword_research_items', function (Blueprint $table) {
            $table->decimal('keyword_density_pct', 5, 2)->nullable()->after('business_relevance_score');
            $table->unsignedInteger('keyword_traffic')->nullable()->after('keyword_density_pct');
        });
    }

    public function down(): void
    {
        Schema::table('keyword_research_items', function (Blueprint $table) {
            $table->dropColumn(['keyword_density_pct', 'keyword_traffic']);
        });
    }
};

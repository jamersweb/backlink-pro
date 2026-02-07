<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga4_daily_metrics', function (Blueprint $table) {
            if (!Schema::hasColumn('ga4_daily_metrics', 'page_views')) {
                if (Schema::hasColumn('ga4_daily_metrics', 'avg_engagement_time_sec')) {
                    $table->integer('page_views')->nullable()->after('avg_engagement_time_sec');
                } else {
                    $table->integer('page_views')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ga4_daily_metrics', function (Blueprint $table) {
            if (Schema::hasColumn('ga4_daily_metrics', 'page_views')) {
                $table->dropColumn('page_views');
            }
        });
    }
};

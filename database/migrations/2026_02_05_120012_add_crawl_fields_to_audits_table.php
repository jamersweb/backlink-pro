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
        Schema::table('audits', function (Blueprint $table) {
            $table->integer('pages_limit')->default(25)->after('error');
            $table->integer('crawl_depth')->default(2)->after('pages_limit');
            $table->integer('pages_scanned')->default(0)->after('crawl_depth');
            $table->integer('pages_discovered')->default(0)->after('pages_scanned');
            $table->integer('progress_percent')->default(0)->after('pages_discovered');
            $table->json('crawl_stats')->nullable()->after('progress_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn([
                'pages_limit',
                'crawl_depth',
                'pages_scanned',
                'pages_discovered',
                'progress_percent',
                'crawl_stats',
            ]);
        });
    }
};

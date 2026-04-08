<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            if (!Schema::hasColumn('audits', 'crawl_module_flags')) {
                $table->json('crawl_module_flags')->nullable()->after('crawl_stats');
            }
            if (!Schema::hasColumn('audits', 'report_modules')) {
                $table->json('report_modules')->nullable()->after('crawl_module_flags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('audits', 'report_modules')) {
                $drop[] = 'report_modules';
            }
            if (Schema::hasColumn('audits', 'crawl_module_flags')) {
                $drop[] = 'crawl_module_flags';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};


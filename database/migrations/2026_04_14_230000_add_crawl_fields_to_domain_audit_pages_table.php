<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_audit_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_audit_pages', 'crawl_depth')) {
                $table->unsignedSmallInteger('crawl_depth')->default(0)->after('path');
            }
            if (!Schema::hasColumn('domain_audit_pages', 'discovered_from_url')) {
                $table->string('discovered_from_url')->nullable()->after('crawl_depth');
            }
            if (!Schema::hasColumn('domain_audit_pages', 'outlinks_count')) {
                $table->unsignedInteger('outlinks_count')->default(0)->after('word_count');
            }

            $table->index(['domain_audit_id', 'crawl_depth'], 'domain_audit_pages_audit_depth_idx');
        });
    }

    public function down(): void
    {
        Schema::table('domain_audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('domain_audit_pages', 'outlinks_count')) {
                $table->dropColumn('outlinks_count');
            }
            if (Schema::hasColumn('domain_audit_pages', 'discovered_from_url')) {
                $table->dropColumn('discovered_from_url');
            }
            if (Schema::hasColumn('domain_audit_pages', 'crawl_depth')) {
                $table->dropColumn('crawl_depth');
            }

            $table->dropIndex('domain_audit_pages_audit_depth_idx');
        });
    }
};


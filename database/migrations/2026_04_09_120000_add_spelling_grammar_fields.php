<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_pages', 'visible_main_text')) {
                $table->mediumText('visible_main_text')->nullable()->after('content_excerpt');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            if (!Schema::hasColumn('audits', 'spelling_allowlist')) {
                $table->json('spelling_allowlist')->nullable()->after('crawl_module_flags');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'spelling_allowlist')) {
                $table->json('spelling_allowlist')->nullable()->after('billing_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('audit_pages', 'visible_main_text')) {
                $table->dropColumn('visible_main_text');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            if (Schema::hasColumn('audits', 'spelling_allowlist')) {
                $table->dropColumn('spelling_allowlist');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'spelling_allowlist')) {
                $table->dropColumn('spelling_allowlist');
            }
        });
    }
};

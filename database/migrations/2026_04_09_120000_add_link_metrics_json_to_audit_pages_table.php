<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_pages', 'link_metrics_json')) {
                $table->json('link_metrics_json')->nullable()->after('js_render_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('audit_pages', 'link_metrics_json')) {
                $table->dropColumn('link_metrics_json');
            }
        });
    }
};

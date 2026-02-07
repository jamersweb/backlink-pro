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
        Schema::table('audit_pages', function (Blueprint $table) {
            $table->json('lighthouse_mobile')->nullable()->after('html_size_bytes');
            $table->json('lighthouse_desktop')->nullable()->after('lighthouse_mobile');
            $table->json('performance_metrics')->nullable()->after('lighthouse_desktop');
            $table->json('security_headers')->nullable()->after('performance_metrics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            $table->dropColumn([
                'lighthouse_mobile',
                'lighthouse_desktop',
                'performance_metrics',
                'security_headers',
            ]);
        });
    }
};

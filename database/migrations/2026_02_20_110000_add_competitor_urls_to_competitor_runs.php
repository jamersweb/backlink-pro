<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('competitor_runs')) {
            return;
        }
        if (Schema::hasColumn('competitor_runs', 'competitor_urls')) {
            return;
        }
        Schema::table('competitor_runs', function (Blueprint $table) {
            $table->json('competitor_urls')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('competitor_runs') || !Schema::hasColumn('competitor_runs', 'competitor_urls')) {
            return;
        }
        Schema::table('competitor_runs', function (Blueprint $table) {
            $table->dropColumn('competitor_urls');
        });
    }
};

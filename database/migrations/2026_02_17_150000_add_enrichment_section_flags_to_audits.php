<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section-ready flags: when set, that section (PSI/GA4/GSC) has been
     * fetched (or attempted). Frontend uses these to show loaders vs content.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->timestamp('psi_ready_at')->nullable()->after('progress_stage');
            $table->timestamp('ga4_ready_at')->nullable()->after('psi_ready_at');
            $table->timestamp('gsc_ready_at')->nullable()->after('ga4_ready_at');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['psi_ready_at', 'ga4_ready_at', 'gsc_ready_at']);
        });
    }
};

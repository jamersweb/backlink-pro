<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('monitor_id')->nullable()->after('organization_id')->constrained('audit_monitors')->onDelete('set null');
            $table->index('monitor_id');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['monitor_id']);
            $table->dropColumn('monitor_id');
        });
    }
};

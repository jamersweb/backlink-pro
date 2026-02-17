<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            if (!Schema::hasColumn('audits', 'progress_stage')) {
                $table->string('progress_stage', 50)->nullable()->after('progress_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn('progress_stage');
        });
    }
};

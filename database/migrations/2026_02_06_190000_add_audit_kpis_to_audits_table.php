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
            if (!Schema::hasColumn('audits', 'category_grades')) {
                $table->json('category_grades')->nullable()->after('category_scores');
            }
            if (!Schema::hasColumn('audits', 'recommendations_count')) {
                $table->integer('recommendations_count')->default(0)->after('category_grades');
            }
            if (!Schema::hasColumn('audits', 'audit_kpis')) {
                $table->json('audit_kpis')->nullable()->after('recommendations_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('audits', 'category_grades')) {
                $columns[] = 'category_grades';
            }
            if (Schema::hasColumn('audits', 'recommendations_count')) {
                $columns[] = 'recommendations_count';
            }
            if (Schema::hasColumn('audits', 'audit_kpis')) {
                $columns[] = 'audit_kpis';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

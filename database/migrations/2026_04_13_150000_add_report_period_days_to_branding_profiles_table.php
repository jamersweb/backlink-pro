<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('report_period_days')->default(30)->after('report_footer_text');
        });
    }

    public function down(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->dropColumn('report_period_days');
        });
    }
};

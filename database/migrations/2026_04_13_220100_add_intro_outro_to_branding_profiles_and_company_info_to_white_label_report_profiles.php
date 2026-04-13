<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->text('report_intro_text')->nullable()->after('report_footer_text');
            $table->text('report_outro_text')->nullable()->after('report_intro_text');
        });

        Schema::table('white_label_report_profiles', function (Blueprint $table) {
            $table->text('client_company_info')->nullable()->after('client_website');
        });
    }

    public function down(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->dropColumn(['report_intro_text', 'report_outro_text']);
        });

        Schema::table('white_label_report_profiles', function (Blueprint $table) {
            $table->dropColumn('client_company_info');
        });
    }
};

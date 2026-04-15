<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branding_profiles')) {
            Schema::table('branding_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('branding_profiles', 'report_intro_text')) {
                    $table->text('report_intro_text')->nullable()->after('report_footer_text');
                }
                if (!Schema::hasColumn('branding_profiles', 'report_outro_text')) {
                    $table->text('report_outro_text')->nullable()->after('report_intro_text');
                }
            });
        }

        if (Schema::hasTable('white_label_report_profiles')) {
            Schema::table('white_label_report_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('white_label_report_profiles', 'client_company_info')) {
                    $table->text('client_company_info')->nullable()->after('client_website');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branding_profiles')) {
            Schema::table('branding_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('branding_profiles', 'report_intro_text')) {
                    $table->dropColumn('report_intro_text');
                }
                if (Schema::hasColumn('branding_profiles', 'report_outro_text')) {
                    $table->dropColumn('report_outro_text');
                }
            });
        }

        if (Schema::hasTable('white_label_report_profiles')) {
            Schema::table('white_label_report_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('white_label_report_profiles', 'client_company_info')) {
                    $table->dropColumn('client_company_info');
                }
            });
        }
    }
};

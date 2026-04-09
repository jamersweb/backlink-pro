<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->boolean('white_label_enabled')->default(false)->after('organization_id');
            $table->string('website')->nullable()->after('secondary_color');
            $table->string('support_email')->nullable()->after('website');
            $table->boolean('use_custom_cover_title')->default(false)->after('report_footer_text');
            $table->string('custom_cover_title')->nullable()->after('use_custom_cover_title');
        });
    }

    public function down(): void
    {
        Schema::table('branding_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'white_label_enabled',
                'website',
                'support_email',
                'use_custom_cover_title',
                'custom_cover_title',
            ]);
        });
    }
};

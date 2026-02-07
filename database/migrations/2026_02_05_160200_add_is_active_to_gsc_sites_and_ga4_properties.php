<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('gsc_sites', 'is_active')) {
            Schema::table('gsc_sites', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('permission_level');
            });
        }

        if (!Schema::hasColumn('ga4_properties', 'is_active')) {
            Schema::table('ga4_properties', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('display_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('gsc_sites', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('ga4_properties', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

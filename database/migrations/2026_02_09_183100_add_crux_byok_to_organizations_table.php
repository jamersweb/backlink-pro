<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'crux_api_key_encrypted')) {
                $table->text('crux_api_key_encrypted')->nullable()->after('pagespeed_last_key_verified_at');
            }
            if (!Schema::hasColumn('organizations', 'crux_byok_enabled')) {
                $table->boolean('crux_byok_enabled')->default(false)->after('crux_api_key_encrypted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'crux_byok_enabled')) {
                $table->dropColumn('crux_byok_enabled');
            }
            if (Schema::hasColumn('organizations', 'crux_api_key_encrypted')) {
                $table->dropColumn('crux_api_key_encrypted');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'pagespeed_api_key_encrypted')) {
                $table->text('pagespeed_api_key_encrypted')->nullable()->after('billing_address');
            }
            if (!Schema::hasColumn('organizations', 'pagespeed_byok_enabled')) {
                $table->boolean('pagespeed_byok_enabled')->default(false)->after('pagespeed_api_key_encrypted');
            }
            if (!Schema::hasColumn('organizations', 'pagespeed_last_key_verified_at')) {
                $table->timestamp('pagespeed_last_key_verified_at')->nullable()->after('pagespeed_byok_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'pagespeed_api_key_encrypted',
                'pagespeed_byok_enabled',
                'pagespeed_last_key_verified_at',
            ]);
        });
    }
};

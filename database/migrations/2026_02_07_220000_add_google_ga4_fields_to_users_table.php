<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_provider')->nullable()->after('remember_token');
            $table->text('google_access_token')->nullable()->after('google_provider');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->timestamp('google_connected_at')->nullable()->after('google_token_expires_at');
            $table->string('google_email')->nullable()->after('google_connected_at');
            $table->string('ga4_property_id')->nullable()->after('google_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_provider',
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_connected_at',
                'google_email',
                'ga4_property_id',
            ]);
        });
    }
};

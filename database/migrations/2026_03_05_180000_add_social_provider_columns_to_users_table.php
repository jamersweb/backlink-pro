<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('ga4_property_id');
            $table->string('apple_id')->nullable()->after('google_id');
            $table->string('github_id')->nullable()->after('apple_id');
            $table->string('microsoft_id')->nullable()->after('github_id');
            $table->string('avatar_url')->nullable()->after('microsoft_id');

            $table->index('google_id');
            $table->index('apple_id');
            $table->index('github_id');
            $table->index('microsoft_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['apple_id']);
            $table->dropIndex(['github_id']);
            $table->dropIndex(['microsoft_id']);
            $table->dropColumn(['google_id', 'apple_id', 'github_id', 'microsoft_id', 'avatar_url']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable();
                $table->index('google_id');
            }

            if (!Schema::hasColumn('users', 'github_id')) {
                $table->string('github_id')->nullable();
                $table->index('github_id');
            }

            if (!Schema::hasColumn('users', 'facebook_id')) {
                $table->string('facebook_id')->nullable();
                $table->index('facebook_id');
            }

            if (!Schema::hasColumn('users', 'microsoft_id')) {
                $table->string('microsoft_id')->nullable();
                $table->index('microsoft_id');
            }

            if (!Schema::hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable();
                $table->index('apple_id');
            }

            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_id')) {
                try {
                    $table->dropIndex(['google_id']);
                } catch (\Throwable $e) {
                    // no-op
                }
                $table->dropColumn('google_id');
            }

            if (Schema::hasColumn('users', 'github_id')) {
                try {
                    $table->dropIndex(['github_id']);
                } catch (\Throwable $e) {
                    // no-op
                }
                $table->dropColumn('github_id');
            }

            if (Schema::hasColumn('users', 'facebook_id')) {
                try {
                    $table->dropIndex(['facebook_id']);
                } catch (\Throwable $e) {
                    // no-op
                }
                $table->dropColumn('facebook_id');
            }

            if (Schema::hasColumn('users', 'microsoft_id')) {
                try {
                    $table->dropIndex(['microsoft_id']);
                } catch (\Throwable $e) {
                    // no-op
                }
                $table->dropColumn('microsoft_id');
            }

            if (Schema::hasColumn('users', 'apple_id')) {
                try {
                    $table->dropIndex(['apple_id']);
                } catch (\Throwable $e) {
                    // no-op
                }
                $table->dropColumn('apple_id');
            }

            if (Schema::hasColumn('users', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }
        });
    }
};

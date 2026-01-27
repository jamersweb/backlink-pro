<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('url')->nullable()->after('name');
            $table->string('host')->nullable()->after('url');
            $table->enum('platform', ['wordpress', 'shopify', 'custom', 'webflow', 'wix', 'squarespace', 'other'])->nullable()->after('host');
            $table->enum('verification_status', ['unverified', 'pending', 'verified'])->default('unverified')->after('platform');
            $table->enum('verification_method', ['dns_txt', 'html_file', 'meta_tag'])->nullable()->after('verification_status');
            $table->string('verification_token')->nullable()->after('verification_method');
            $table->timestamp('verified_at')->nullable()->after('verification_token');
        });

        // Add indexes and unique constraint
        Schema::table('domains', function (Blueprint $table) {
            $table->index('host');
            $table->index(['user_id', 'host']);
            // Unique constraint on (user_id, host) - MySQL allows multiple NULLs in unique indexes
            $table->unique(['user_id', 'host'], 'domains_user_id_host_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // Drop unique index first
            $table->dropUnique('domains_user_id_host_unique');
            
            // Drop regular indexes
            $table->dropIndex(['host']);
            $table->dropIndex(['user_id', 'host']);
            
            // Drop columns
            $table->dropColumn([
                'url',
                'host',
                'platform',
                'verification_status',
                'verification_method',
                'verification_token',
                'verified_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            $table->integer('h4_count')->default(0)->after('h3_count');
            $table->integer('h5_count')->default(0)->after('h4_count');
            $table->integer('h6_count')->default(0)->after('h5_count');
            $table->text('h1_text')->nullable()->after('h6_count');
            $table->string('lang', 32)->nullable()->after('h1_text');
            $table->boolean('hreflang_present')->default(false)->after('lang');
            $table->boolean('viewport_present')->default(false)->after('hreflang_present');
            $table->boolean('favicon_present')->default(false)->after('viewport_present');
            $table->string('analytics_tool', 64)->nullable()->after('favicon_present');
            $table->integer('iframes_count')->default(0)->after('analytics_tool');
            $table->boolean('flash_used')->default(false)->after('iframes_count');
            $table->json('social_links')->nullable()->after('flash_used');
            $table->string('x_robots_tag', 255)->nullable()->after('social_links');
            $table->string('server_header', 255)->nullable()->after('x_robots_tag');
            $table->string('x_powered_by', 255)->nullable()->after('server_header');
            $table->string('content_type', 255)->nullable()->after('x_powered_by');
            $table->string('charset', 64)->nullable()->after('content_type');
            $table->text('content_excerpt')->nullable()->after('charset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            $table->dropColumn([
                'h4_count',
                'h5_count',
                'h6_count',
                'h1_text',
                'lang',
                'hreflang_present',
                'viewport_present',
                'favicon_present',
                'analytics_tool',
                'iframes_count',
                'flash_used',
                'social_links',
                'x_robots_tag',
                'server_header',
                'x_powered_by',
                'content_type',
                'charset',
                'content_excerpt',
            ]);
        });
    }
};

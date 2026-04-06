<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            if (! Schema::hasColumn('audits', 'forms_auth_login_url')) {
                $table->string('forms_auth_login_url', 2048)->nullable()->after('custom_extraction_rules');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_username')) {
                $table->text('forms_auth_username')->nullable()->after('forms_auth_login_url');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_password')) {
                $table->text('forms_auth_password')->nullable()->after('forms_auth_username');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_username_selector')) {
                $table->string('forms_auth_username_selector', 512)->nullable()->after('forms_auth_password');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_password_selector')) {
                $table->string('forms_auth_password_selector', 512)->nullable()->after('forms_auth_username_selector');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_submit_selector')) {
                $table->string('forms_auth_submit_selector', 512)->nullable()->after('forms_auth_password_selector');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_success_indicator')) {
                $table->string('forms_auth_success_indicator', 512)->nullable()->after('forms_auth_submit_selector');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_state')) {
                $table->json('forms_auth_state')->nullable()->after('forms_auth_success_indicator');
            }
            if (! Schema::hasColumn('audits', 'forms_auth_cookie_jar')) {
                $table->text('forms_auth_cookie_jar')->nullable()->after('forms_auth_state');
            }
        });

        Schema::table('audit_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_pages', 'auth_crawl_metadata')) {
                $table->json('auth_crawl_metadata')->nullable()->after('response_headers_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_pages', function (Blueprint $table) {
            if (Schema::hasColumn('audit_pages', 'auth_crawl_metadata')) {
                $table->dropColumn('auth_crawl_metadata');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            foreach ([
                'forms_auth_cookie_jar',
                'forms_auth_state',
                'forms_auth_success_indicator',
                'forms_auth_submit_selector',
                'forms_auth_password_selector',
                'forms_auth_username_selector',
                'forms_auth_password',
                'forms_auth_username',
                'forms_auth_login_url',
            ] as $col) {
                if (Schema::hasColumn('audits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_issues', 'audit_run_id')) {
                $table->unsignedBigInteger('audit_run_id')->nullable()->after('audit_id');
            }
            if (!Schema::hasColumn('audit_issues', 'url')) {
                $table->string('url', 2048)->nullable()->after('audit_run_id');
            }
            if (!Schema::hasColumn('audit_issues', 'module_key')) {
                $table->string('module_key', 64)->nullable()->after('url');
            }
            if (!Schema::hasColumn('audit_issues', 'issue_type')) {
                $table->string('issue_type', 100)->nullable()->after('module_key');
            }
            if (!Schema::hasColumn('audit_issues', 'severity')) {
                $table->string('severity', 32)->nullable()->after('issue_type');
            }
            if (!Schema::hasColumn('audit_issues', 'status')) {
                $table->string('status', 32)->nullable()->after('severity');
            }
            if (!Schema::hasColumn('audit_issues', 'message')) {
                $table->text('message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('audit_issues', 'details_json')) {
                $table->json('details_json')->nullable()->after('message');
            }
            if (!Schema::hasColumn('audit_issues', 'discovered_at')) {
                $table->timestamp('discovered_at')->nullable()->after('details_json');
            }
        });

        DB::statement('UPDATE audit_issues SET audit_run_id = audit_id WHERE audit_run_id IS NULL');
        DB::statement('UPDATE audit_issues SET issue_type = code WHERE issue_type IS NULL');
        DB::statement("UPDATE audit_issues SET module_key = CASE 
            WHEN category = 'onpage' THEN 'on_page_seo'
            WHEN category IN ('social','local','security','usability') THEN 'integrations'
            WHEN category IS NULL THEN 'overview'
            ELSE category
        END WHERE module_key IS NULL");
        DB::statement("UPDATE audit_issues SET severity = CASE impact WHEN 'high' THEN 'critical' WHEN 'medium' THEN 'warning' ELSE 'info' END WHERE severity IS NULL");
        DB::statement("UPDATE audit_issues SET status = 'open' WHERE status IS NULL");
        DB::statement('UPDATE audit_issues SET message = COALESCE(title, description, code) WHERE message IS NULL');
        DB::statement('UPDATE audit_issues SET discovered_at = created_at WHERE discovered_at IS NULL');

        Schema::table('audit_issues', function (Blueprint $table) {
            $table->index(['audit_run_id', 'module_key', 'severity'], 'audit_issues_run_module_severity_idx');
            $table->index('issue_type');
            $table->index('status');
            $table->index('discovered_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_issues', function (Blueprint $table) {
            $table->dropIndex('audit_issues_run_module_severity_idx');
            $table->dropIndex(['issue_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['discovered_at']);
            $table->dropColumn([
                'audit_run_id',
                'url',
                'module_key',
                'issue_type',
                'severity',
                'status',
                'message',
                'details_json',
                'discovered_at',
            ]);
        });
    }
};


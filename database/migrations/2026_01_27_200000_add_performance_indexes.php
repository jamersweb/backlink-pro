<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes based on common query patterns
     */
    public function up(): void
    {
        // Automation jobs - frequently queried by campaign and status
        $this->addIndexIfNotExists('automation_jobs', 'automation_jobs_campaign_status_index', ['campaign_id', 'status']);
        $this->addIndexIfNotExists('automation_jobs', 'automation_jobs_user_status_index', ['user_id', 'status']);

        // Automation tasks - frequently queried by campaign and status
        $this->addIndexIfNotExists('automation_tasks', 'automation_tasks_campaign_status_index', ['campaign_id', 'status']);
        $this->addIndexIfNotExists('automation_tasks', 'automation_tasks_type_status_index', ['type', 'status']);

        // Backlink opportunities - frequently queried by campaign and status
        $this->addIndexIfNotExists('backlink_opportunities', 'backlink_opportunities_campaign_status_index', ['campaign_id', 'status']);
        $this->addIndexIfNotExists('backlink_opportunities', 'backlink_opportunities_backlink_id_index', ['backlink_id']);

        // Domain backlinks - frequently queried by run and status
        $this->addIndexIfNotExists('domain_backlinks', 'domain_backlinks_run_status_index', ['domain_backlink_run_id', 'action_status']);

        // Notifications - frequently queried by user and status
        $this->addIndexIfNotExists('notifications', 'notifications_user_status_type_index', ['user_id', 'status', 'type']);

        // User notifications - frequently queried by user and read status
        $this->addIndexIfNotExists('user_notifications', 'user_notifications_user_read_index', ['user_id', 'read']);

        // Activity logs - frequently queried by subject
        $this->addIndexIfNotExists('activity_logs', 'activity_logs_subject_index', ['subject_type', 'subject_id']);

        // System activity logs - frequently queried by feature and user
        $this->addIndexIfNotExists('system_activity_logs', 'system_activity_logs_feature_user_index', ['feature', 'user_id']);

        // Rank keywords - frequently queried by domain and active status
        $this->addIndexIfNotExists('rank_keywords', 'rank_keywords_domain_active_index', ['domain_id', 'is_active']);

        // Domain tasks - frequently queried by domain and status
        $this->addIndexIfNotExists('domain_tasks', 'domain_tasks_domain_status_index', ['domain_id', 'status', 'priority']);

        // Campaigns - frequently queried by user and status
        $this->addIndexIfNotExists('campaigns', 'campaigns_user_status_index', ['user_id', 'status']);

        // Site accounts - frequently queried by user and campaign
        $this->addIndexIfNotExists('site_accounts', 'site_accounts_user_campaign_index', ['user_id', 'campaign_id']);
        $this->addIndexIfNotExists('site_accounts', 'site_accounts_status_index', ['status']);

        // Captcha logs - frequently queried by campaign
        $this->addIndexIfNotExists('captcha_logs', 'captcha_logs_campaign_index', ['campaign_id']);

        // Backlinks - frequently queried by campaign and status
        $this->addIndexIfNotExists('backlinks', 'backlinks_campaign_status_index', ['campaign_id', 'status']);
        $this->addIndexIfNotExists('backlinks', 'backlinks_site_type_status_index', ['site_type', 'status']);
    }

    /**
     * Add index if table exists and index doesn't exist
     */
    protected function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // Check if index already exists
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        // Verify all columns exist
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return; // Skip if any column doesn't exist
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName, $columns) {
            $blueprint->index($columns, $indexName);
        });
    }

    /**
     * Check if an index exists on a table
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($result) > 0;
        }

        if ($driver === 'pgsql') {
            $result = DB::select("SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
            return count($result) > 0;
        }

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?", [$indexName]);
            return count($result) > 0;
        }

        // For other drivers, try to create and catch the error
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'automation_jobs' => ['automation_jobs_campaign_status_index', 'automation_jobs_user_status_index'],
            'automation_tasks' => ['automation_tasks_campaign_status_index', 'automation_tasks_type_status_index'],
            'backlink_opportunities' => ['backlink_opportunities_campaign_status_index', 'backlink_opportunities_backlink_id_index'],
            'domain_backlinks' => ['domain_backlinks_run_status_index'],
            'notifications' => ['notifications_user_status_type_index'],
            'user_notifications' => ['user_notifications_user_read_index'],
            'activity_logs' => ['activity_logs_subject_index'],
            'system_activity_logs' => ['system_activity_logs_feature_user_index'],
            'rank_keywords' => ['rank_keywords_domain_active_index'],
            'domain_tasks' => ['domain_tasks_domain_status_index'],
            'campaigns' => ['campaigns_user_status_index'],
            'site_accounts' => ['site_accounts_user_campaign_index', 'site_accounts_status_index'],
            'captcha_logs' => ['captcha_logs_campaign_index'],
            'backlinks' => ['backlinks_campaign_status_index', 'backlinks_site_type_status_index'],
        ];

        foreach ($indexes as $table => $indexNames) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexNames as $indexName) {
                if ($this->indexExists($table, $indexName)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                        $blueprint->dropIndex($indexName);
                    });
                }
            }
        }
    }
};

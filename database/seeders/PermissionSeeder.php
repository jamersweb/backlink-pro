<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions organized by module
     */
    protected array $permissions = [
        // Dashboard
        'dashboard' => [
            'dashboard.view' => 'View Dashboard',
        ],

        // Campaigns
        'campaigns' => [
            'campaigns.view' => 'View Campaigns',
            'campaigns.create' => 'Create Campaigns',
            'campaigns.edit' => 'Edit Campaigns',
            'campaigns.delete' => 'Delete Campaigns',
            'campaigns.pause' => 'Pause Campaigns',
            'campaigns.resume' => 'Resume Campaigns',
            'campaigns.export' => 'Export Campaigns',
        ],

        // Domains
        'domains' => [
            'domains.view' => 'View Domains',
            'domains.create' => 'Create Domains',
            'domains.edit' => 'Edit Domains',
            'domains.delete' => 'Delete Domains',
        ],

        // Backlinks
        'backlinks' => [
            'backlinks.view' => 'View Backlinks',
            'backlinks.create' => 'Create Backlinks',
            'backlinks.export' => 'Export Backlinks',
            'backlinks.recheck' => 'Recheck Backlinks',
        ],

        // Domain Audits
        'domain_audits' => [
            'domain_audits.view' => 'View Domain Audits',
            'domain_audits.create' => 'Create Domain Audits',
            'domain_audits.export' => 'Export Domain Audits',
        ],

        // Domain Backlinks
        'domain_backlinks' => [
            'domain_backlinks.view' => 'View Domain Backlinks',
            'domain_backlinks.import' => 'Import Domain Backlinks',
            'domain_backlinks.export' => 'Export Domain Backlinks',
        ],

        // Domain Meta Editor
        'domain_meta' => [
            'domain_meta.view' => 'View Domain Meta',
            'domain_meta.edit' => 'Edit Domain Meta',
            'domain_meta.publish' => 'Publish Domain Meta',
            'domain_meta.connect' => 'Connect Domain Meta',
        ],

        // Domain Content
        'domain_content' => [
            'domain_content.view' => 'View Domain Content',
            'domain_content.create' => 'Create Content Briefs',
            'domain_content.edit' => 'Edit Content Briefs',
            'domain_content.export' => 'Export Content Briefs',
        ],

        // Rank Tracking
        'rank_tracking' => [
            'rank_tracking.view' => 'View Rank Tracking',
            'rank_tracking.manage_keywords' => 'Manage Keywords',
            'rank_tracking.run' => 'Run Rank Checks',
        ],

        // Domain Insights
        'domain_insights' => [
            'domain_insights.view' => 'View Domain Insights',
            'domain_insights.run' => 'Run Insights',
            'domain_insights.manage_alerts' => 'Manage Alerts',
        ],

        // Domain Planner
        'domain_planner' => [
            'domain_planner.view' => 'View Domain Planner',
            'domain_planner.generate' => 'Generate Plans',
            'domain_planner.apply' => 'Apply Plans',
        ],

        // Domain Automation
        'domain_automation' => [
            'domain_automation.view' => 'View Domain Automation',
            'domain_automation.create' => 'Create Automation Campaigns',
            'domain_automation.manage' => 'Manage Automation Campaigns',
            'domain_automation.import' => 'Import Automation Targets',
        ],

        // Domain Reports
        'domain_reports' => [
            'domain_reports.view' => 'View Domain Reports',
            'domain_reports.create' => 'Create Domain Reports',
            'domain_reports.share' => 'Share Domain Reports',
        ],

        // Domain Integrations
        'domain_integrations' => [
            'domain_integrations.view' => 'View Domain Integrations',
            'domain_integrations.connect' => 'Connect Integrations',
            'domain_integrations.disconnect' => 'Disconnect Integrations',
        ],

        // Site Accounts
        'site_accounts' => [
            'site_accounts.view' => 'View Site Accounts',
            'site_accounts.create' => 'Create Site Accounts',
            'site_accounts.edit' => 'Edit Site Accounts',
            'site_accounts.delete' => 'Delete Site Accounts',
        ],

        // Settings
        'settings' => [
            'settings.view' => 'View Settings',
            'settings.update' => 'Update Settings',
        ],

        // Notifications
        'notifications' => [
            'notifications.view' => 'View Notifications',
            'notifications.manage' => 'Manage Notifications',
        ],

        // Team
        'team' => [
            'team.view' => 'View Team',
            'team.invite' => 'Invite Team Members',
            'team.manage' => 'Manage Team Members',
            'team.remove' => 'Remove Team Members',
        ],

        // Profile
        'profile' => [
            'profile.view' => 'View Profile',
            'profile.update' => 'Update Profile',
        ],

        // Subscription
        'subscription' => [
            'subscription.view' => 'View Subscription',
            'subscription.manage' => 'Manage Subscription',
        ],

        // Reports
        'reports' => [
            'reports.view' => 'View Reports',
            'reports.export' => 'Export Reports',
        ],

        // Activity
        'activity' => [
            'activity.view' => 'View Activity',
        ],

        // Gmail/OAuth
        'gmail' => [
            'gmail.view' => 'View Gmail Accounts',
            'gmail.connect' => 'Connect Gmail',
            'gmail.disconnect' => 'Disconnect Gmail',
        ],

        // Marketing Pages (Frontend)
        'marketing' => [
            'marketing.view' => 'View Marketing Pages',
            'marketing.home' => 'View Homepage',
            'marketing.pricing' => 'View Pricing Page',
            'marketing.resources' => 'View Resources',
            'marketing.blog' => 'View Blog',
            'marketing.case_studies' => 'View Case Studies',
            'marketing.workflows' => 'View Workflows',
            'marketing.solutions' => 'View Solutions',
            'marketing.contact' => 'View Contact Page',
            'marketing.about' => 'View About Page',
        ],

        // ============= ADMIN PERMISSIONS =============

        // Admin Dashboard
        'admin_dashboard' => [
            'admin.dashboard.view' => 'View Admin Dashboard',
        ],

        // Admin Users
        'admin_users' => [
            'admin.users.view' => 'View All Users',
            'admin.users.edit' => 'Edit Users',
            'admin.users.reset_password' => 'Reset User Passwords',
            'admin.users.assign_roles' => 'Assign User Roles',
            'admin.users.assign_permissions' => 'Assign User Permissions',
        ],

        // Admin Plans
        'admin_plans' => [
            'admin.plans.view' => 'View All Plans',
            'admin.plans.create' => 'Create Plans',
            'admin.plans.edit' => 'Edit Plans',
            'admin.plans.delete' => 'Delete Plans',
        ],

        // Admin Subscriptions
        'admin_subscriptions' => [
            'admin.subscriptions.view' => 'View All Subscriptions',
            'admin.subscriptions.manage' => 'Manage Subscriptions',
        ],

        // Admin Leads
        'admin_leads' => [
            'admin.leads.view' => 'View Leads',
            'admin.leads.manage' => 'Manage Leads',
        ],

        // Admin Campaigns
        'admin_campaigns' => [
            'admin.campaigns.view' => 'View All Campaigns',
            'admin.campaigns.edit' => 'Edit Any Campaign',
            'admin.campaigns.delete' => 'Delete Any Campaign',
            'admin.campaigns.create_tasks' => 'Create Campaign Tasks',
        ],

        // Admin Categories
        'admin_categories' => [
            'admin.categories.view' => 'View Categories',
            'admin.categories.create' => 'Create Categories',
            'admin.categories.edit' => 'Edit Categories',
            'admin.categories.delete' => 'Delete Categories',
        ],

        // Admin Backlink Opportunities
        'admin_opportunities' => [
            'admin.opportunities.view' => 'View Backlink Opportunities',
            'admin.opportunities.create' => 'Create Backlink Opportunities',
            'admin.opportunities.edit' => 'Edit Backlink Opportunities',
            'admin.opportunities.delete' => 'Delete Backlink Opportunities',
            'admin.opportunities.import' => 'Import Backlink Opportunities',
            'admin.opportunities.export' => 'Export Backlink Opportunities',
        ],

        // Admin Backlinks
        'admin_backlinks' => [
            'admin.backlinks.view' => 'View All Backlinks',
            'admin.backlinks.create' => 'Create Backlinks',
            'admin.backlinks.import' => 'Import Backlinks',
            'admin.backlinks.export' => 'Export Backlinks',
        ],

        // Admin Automation Tasks
        'admin_automation_tasks' => [
            'admin.automation_tasks.view' => 'View Automation Tasks',
            'admin.automation_tasks.retry' => 'Retry Automation Tasks',
            'admin.automation_tasks.cancel' => 'Cancel Automation Tasks',
        ],

        // Admin Proxies
        'admin_proxies' => [
            'admin.proxies.view' => 'View Proxies',
            'admin.proxies.create' => 'Create Proxies',
            'admin.proxies.edit' => 'Edit Proxies',
            'admin.proxies.delete' => 'Delete Proxies',
            'admin.proxies.test' => 'Test Proxies',
        ],

        // Admin Captcha Logs
        'admin_captcha' => [
            'admin.captcha.view' => 'View Captcha Logs',
        ],

        // Admin System Health
        'admin_system' => [
            'admin.system.view' => 'View System Health',
            'admin.system.activity' => 'View System Activity',
            'admin.system.failures' => 'View System Failures',
            'admin.system.retry_jobs' => 'Retry Failed Jobs',
            'admin.system.flush_jobs' => 'Flush Failed Jobs',
        ],

        // Admin Settings
        'admin_settings' => [
            'admin.settings.view' => 'View Admin Settings',
            'admin.settings.update' => 'Update Admin Settings',
            'admin.settings.test_connection' => 'Test Connections',
        ],

        // Admin ML Training
        'admin_ml' => [
            'admin.ml.view' => 'View ML Training',
            'admin.ml.train' => 'Train ML Models',
            'admin.ml.export' => 'Export ML Data',
        ],

        // Admin Blocked Sites
        'admin_blocked_sites' => [
            'admin.blocked_sites.view' => 'View Blocked Sites',
            'admin.blocked_sites.create' => 'Create Blocked Sites',
            'admin.blocked_sites.edit' => 'Edit Blocked Sites',
            'admin.blocked_sites.delete' => 'Delete Blocked Sites',
        ],

        // Admin Marketing Leads
        'admin_marketing_leads' => [
            'admin.marketing_leads.view' => 'View Marketing Leads',
            'admin.marketing_leads.manage' => 'Manage Marketing Leads',
        ],

        // Admin Runs/Retries
        'admin_runs' => [
            'admin.runs.retry' => 'Retry Runs',
        ],

        // Admin Roles & Permissions
        'admin_roles' => [
            'admin.roles.view' => 'View Roles',
            'admin.roles.create' => 'Create Roles',
            'admin.roles.edit' => 'Edit Roles',
            'admin.roles.delete' => 'Delete Roles',
            'admin.permissions.view' => 'View Permissions',
            'admin.permissions.assign' => 'Assign Permissions',
        ],

        // Admin Page Meta / SEO Management
        'admin_page_metas' => [
            'admin.page_metas.view' => 'View Page SEO Settings',
            'admin.page_metas.create' => 'Create Page SEO Settings',
            'admin.page_metas.edit' => 'Edit Page SEO Settings',
            'admin.page_metas.delete' => 'Delete Page SEO Settings',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Creating permissions...');

        // Create all permissions
        $allPermissions = [];
        foreach ($this->permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permissionName => $description) {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
                $allPermissions[] = $permissionName;
                $this->command->info("  Created: {$permissionName}");
            }
        }

        $this->command->info('');
        $this->command->info('Assigning permissions to roles...');

        // Get or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Admin gets ALL permissions
        $adminRole->syncPermissions($allPermissions);
        $this->command->info('  Admin role: ALL permissions assigned');

        // User gets standard user permissions (non-admin)
        $userPermissions = collect($allPermissions)->filter(function ($permission) {
            return !str_starts_with($permission, 'admin.');
        })->toArray();

        $userRole->syncPermissions($userPermissions);
        $this->command->info('  User role: Standard permissions assigned');

        $this->command->info('');
        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Total permissions created: ' . count($allPermissions));
    }

    /**
     * Get all permissions as a flat array
     */
    public function getAllPermissions(): array
    {
        $all = [];
        foreach ($this->permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $name => $description) {
                $all[$name] = $description;
            }
        }
        return $all;
    }

    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule(): array
    {
        return $this->permissions;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'starter',
                'tagline' => 'For small sites testing guardrailed workflows.',
                'price_monthly' => 7900, // $79.00
                'price_annual' => 6700, // $67.00/mo billed annually
                'sort_order' => 1,
                'is_highlighted' => false,
                'badge' => null,
                'is_active' => true,
                'is_public' => true,
                'limits_json' => [
                    'projects' => 1,
                    'monthly_actions' => 2000,
                    'team_seats' => 1,
                    'domains.max_active' => 3,
                    'audits.runs_per_month' => 10,
                    'audits.pages_per_month' => 1000,
                    'backlinks.runs_per_month' => 5,
                    'backlinks.links_fetched_per_month' => 10000,
                    'google.sync_now_per_day' => 3,
                    'meta.publish_per_month' => 50,
                    'insights.runs_per_day' => 5,
                ],
                'display_limits' => [
                    ['label' => 'Projects', 'value' => '1'],
                    ['label' => 'Monthly actions', 'value' => '2,000'],
                    ['label' => 'Team seats', 'value' => '1'],
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                    'comment_workflow' => true,
                    'profile_workflow' => true,
                    'forum_workflow' => false,
                    'guest_workflow' => false,
                    'approvals' => true,
                    'evidence_logs' => true,
                    'monitoring' => 'Basic',
                    'exports' => false,
                    'weekly_summaries' => false,
                    'roles_permissions' => false,
                    'audit_trail' => false,
                    'backlink_types' => ['comment', 'profile'],
                ],
                'includes' => [
                    'Comment + Profile workflows',
                    'Approvals (basic)',
                    'Evidence logs',
                    'Velocity caps + lists',
                ],
                'cta_primary_label' => 'Start Free Trial',
                'cta_primary_href' => '/register?plan=starter',
                'cta_secondary_label' => 'View Workflows',
                'cta_secondary_href' => '/workflows',
            ],
            [
                'name' => 'Growth',
                'code' => 'growth',
                'tagline' => 'For teams scaling with tighter controls and more workflows.',
                'price_monthly' => 19900, // $199.00
                'price_annual' => 16900, // $169.00/mo billed annually
                'sort_order' => 2,
                'is_highlighted' => true,
                'badge' => 'Most popular',
                'is_active' => true,
                'is_public' => true,
                'limits_json' => [
                    'projects' => 5,
                    'monthly_actions' => 8000,
                    'team_seats' => 3,
                    'domains.max_active' => 20,
                    'audits.runs_per_month' => 100,
                    'audits.pages_per_month' => 50000,
                    'backlinks.runs_per_month' => 50,
                    'backlinks.links_fetched_per_month' => 500000,
                    'google.sync_now_per_day' => 20,
                    'meta.publish_per_month' => 1000,
                    'insights.runs_per_day' => 20,
                ],
                'display_limits' => [
                    ['label' => 'Projects', 'value' => '5'],
                    ['label' => 'Monthly actions', 'value' => '8,000'],
                    ['label' => 'Team seats', 'value' => '3'],
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                    'comment_workflow' => true,
                    'profile_workflow' => true,
                    'forum_workflow' => true,
                    'guest_workflow' => false,
                    'approvals' => 'Rules-based',
                    'evidence_logs' => true,
                    'monitoring' => 'Standard',
                    'exports' => 'CSV',
                    'weekly_summaries' => true,
                    'roles_permissions' => false,
                    'audit_trail' => false,
                    'backlink_types' => ['comment', 'profile', 'forum'],
                ],
                'includes' => [
                    'All Starter features',
                    'Forum workflow',
                    'Stronger approval gates (rules)',
                    'CSV exports',
                    'Weekly summaries',
                ],
                'cta_primary_label' => 'Start Free Trial',
                'cta_primary_href' => '/register?plan=growth',
                'cta_secondary_label' => 'See case studies',
                'cta_secondary_href' => '/case-studies',
            ],
            [
                'name' => 'Pro / Agency',
                'code' => 'pro',
                'tagline' => 'For agencies and multi-project operations with audit needs.',
                'price_monthly' => 49900, // $499.00
                'price_annual' => 42400, // $424.00/mo billed annually
                'sort_order' => 3,
                'is_highlighted' => false,
                'badge' => null,
                'is_active' => true,
                'is_public' => true,
                'limits_json' => [
                    'projects' => 20,
                    'monthly_actions' => 25000,
                    'team_seats' => 10,
                    'domains.max_active' => 100,
                    'audits.runs_per_month' => 500,
                    'audits.pages_per_month' => 500000,
                    'backlinks.runs_per_month' => 200,
                    'backlinks.links_fetched_per_month' => 2000000,
                    'google.sync_now_per_day' => 100,
                    'meta.publish_per_month' => 10000,
                    'insights.runs_per_day' => 100,
                ],
                'display_limits' => [
                    ['label' => 'Projects', 'value' => '20+'],
                    ['label' => 'Monthly actions', 'value' => '25,000+'],
                    ['label' => 'Team seats', 'value' => '10+'],
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                    'comment_workflow' => true,
                    'profile_workflow' => true,
                    'forum_workflow' => true,
                    'guest_workflow' => true,
                    'approvals' => 'Advanced',
                    'evidence_logs' => true,
                    'monitoring' => 'Advanced',
                    'exports' => 'White-label',
                    'weekly_summaries' => true,
                    'roles_permissions' => 'Planned',
                    'audit_trail' => 'Planned',
                    'backlink_types' => ['comment', 'profile', 'forum', 'guest'],
                ],
                'includes' => [
                    'All Growth features',
                    'Guest workflow',
                    'Advanced approvals + audit trail',
                    'White-label exports',
                    'Priority support',
                ],
                'cta_primary_label' => 'Talk to sales',
                'cta_primary_href' => '/contact',
                'cta_secondary_label' => 'Security & Trust',
                'cta_secondary_href' => '/security',
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'tagline' => 'Custom solutions for large organizations.',
                'price_monthly' => null, // Custom pricing
                'price_annual' => null,
                'sort_order' => 4,
                'is_highlighted' => false,
                'badge' => null,
                'is_active' => true,
                'is_public' => false, // Not shown on public pricing page
                'limits_json' => [
                    'projects' => -1, // Unlimited
                    'monthly_actions' => -1,
                    'team_seats' => -1,
                    'domains.max_active' => -1,
                    'audits.runs_per_month' => -1,
                    'audits.pages_per_month' => -1,
                    'backlinks.runs_per_month' => -1,
                    'backlinks.links_fetched_per_month' => -1,
                    'google.sync_now_per_day' => -1,
                    'meta.publish_per_month' => -1,
                    'insights.runs_per_day' => -1,
                ],
                'display_limits' => [
                    ['label' => 'Projects', 'value' => 'Unlimited'],
                    ['label' => 'Monthly actions', 'value' => 'Unlimited'],
                    ['label' => 'Team seats', 'value' => 'Unlimited'],
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                    'comment_workflow' => true,
                    'profile_workflow' => true,
                    'forum_workflow' => true,
                    'guest_workflow' => true,
                    'approvals' => 'Custom',
                    'evidence_logs' => true,
                    'monitoring' => 'Custom',
                    'exports' => 'Custom',
                    'weekly_summaries' => true,
                    'roles_permissions' => true,
                    'audit_trail' => true,
                    'backlink_types' => ['comment', 'profile', 'forum', 'guest'],
                ],
                'includes' => [
                    'All Pro features',
                    'Custom integrations',
                    'Dedicated support',
                    'SLA guarantee',
                    'Custom workflows',
                ],
                'cta_primary_label' => 'Contact Us',
                'cta_primary_href' => '/contact',
                'cta_secondary_label' => null,
                'cta_secondary_href' => null,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );
        }
    }
}

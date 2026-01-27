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
                'price_monthly' => null, // Free tier
                'limits_json' => [
                    'domains.max_active' => 3,
                    'audits.runs_per_month' => 10,
                    'audits.pages_per_month' => 1000,
                    'backlinks.runs_per_month' => 5,
                    'backlinks.links_fetched_per_month' => 10000,
                    'google.sync_now_per_day' => 3,
                    'meta.publish_per_month' => 50,
                    'insights.runs_per_day' => 5,
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'code' => 'pro',
                'price_monthly' => 9900, // $99.00
                'limits_json' => [
                    'domains.max_active' => 20,
                    'audits.runs_per_month' => 100,
                    'audits.pages_per_month' => 50000,
                    'backlinks.runs_per_month' => 50,
                    'backlinks.links_fetched_per_month' => 500000,
                    'google.sync_now_per_day' => 20,
                    'meta.publish_per_month' => 1000,
                    'insights.runs_per_day' => 20,
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Agency',
                'code' => 'agency',
                'price_monthly' => 29900, // $299.00
                'limits_json' => [
                    'domains.max_active' => 100,
                    'audits.runs_per_month' => 500,
                    'audits.pages_per_month' => 500000,
                    'backlinks.runs_per_month' => 200,
                    'backlinks.links_fetched_per_month' => 2000000,
                    'google.sync_now_per_day' => 100,
                    'meta.publish_per_month' => 10000,
                    'insights.runs_per_day' => 100,
                ],
                'features_json' => [
                    'website_analyzer' => true,
                    'google_integrations' => true,
                    'backlinks_checker' => true,
                    'meta_editor' => true,
                    'insights' => true,
                ],
                'is_active' => true,
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

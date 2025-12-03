<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started',
                'price' => 0,
                'billing_interval' => 'monthly',
                'max_domains' => 1,
                'max_campaigns' => 1,
                'daily_backlink_limit' => 10,
                'backlink_types' => ['comment', 'profile'],
                'features' => [
                    '1 Domain',
                    '1 Campaign',
                    '10 Daily Backlinks',
                    'Comment & Profile Backlinks',
                    'Basic Analytics',
                    'Email Support',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For small businesses',
                'price' => 29,
                'billing_interval' => 'monthly',
                'max_domains' => 5,
                'max_campaigns' => 5,
                'daily_backlink_limit' => 50,
                'backlink_types' => ['comment', 'profile', 'forum'],
                'features' => [
                    '5 Domains',
                    '5 Campaigns',
                    '50 Daily Backlinks',
                    'Comment, Profile & Forum Backlinks',
                    'Advanced Analytics',
                    'Priority Email Support',
                    'Gmail Integration',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For growing businesses',
                'price' => 79,
                'billing_interval' => 'monthly',
                'max_domains' => 20,
                'max_campaigns' => 20,
                'daily_backlink_limit' => 200,
                'backlink_types' => ['comment', 'profile', 'forum', 'guestposting'],
                'features' => [
                    '20 Domains',
                    '20 Campaigns',
                    '200 Daily Backlinks',
                    'All Backlink Types',
                    'Advanced Analytics & Reports',
                    'Priority Support',
                    'Gmail Integration',
                    'API Access',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Agency',
                'slug' => 'agency',
                'description' => 'For agencies and large teams',
                'price' => 199,
                'billing_interval' => 'monthly',
                'max_domains' => -1, // Unlimited
                'max_campaigns' => -1, // Unlimited
                'daily_backlink_limit' => -1, // Unlimited
                'backlink_types' => ['comment', 'profile', 'forum', 'guestposting'],
                'features' => [
                    'Unlimited Domains',
                    'Unlimited Campaigns',
                    'Unlimited Daily Backlinks',
                    'All Backlink Types',
                    'White-label Reports',
                    'Dedicated Support',
                    'Gmail Integration',
                    'API Access',
                    'Custom Integrations',
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

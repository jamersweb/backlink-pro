<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = config('plans');

        foreach ($plans as $key => $config) {
            Plan::updateOrCreate(
                ['code' => $key],
                [
                    'name' => ucfirst($key) . ' Plan',
                    'code' => $key,
                    'tagline' => $this->getTagline($key),
                    'price_monthly' => $this->getPrice($key, 'monthly'),
                    'price_annual' => $this->getPrice($key, 'annual'),
                    'stripe_price_id_monthly' => env("STRIPE_PRICE_{$key}_MONTHLY"),
                    'stripe_price_id_yearly' => env("STRIPE_PRICE_{$key}_YEARLY"),
                    'limits_json' => $config,
                    'features_json' => $this->getFeatures($key, $config),
                    'is_active' => true,
                    'is_public' => $key !== 'free',
                    'is_highlighted' => $key === 'pro',
                    'sort_order' => $this->getSortOrder($key),
                ]
            );
        }
    }

    protected function getTagline(string $key): string
    {
        return match($key) {
            'free' => 'Perfect for getting started',
            'pro' => 'For serious SEO professionals',
            'agency' => 'Enterprise-grade SEO audits',
            default => '',
        };
    }

    protected function getPrice(string $key, string $interval): ?int
    {
        // Prices in cents
        return match($key) {
            'free' => 0,
            'pro' => $interval === 'monthly' ? 4900 : 49000, // $49/mo or $490/year
            'agency' => $interval === 'monthly' ? 19900 : 199000, // $199/mo or $1990/year
            default => null,
        };
    }

    protected function getFeatures(string $key, array $limits): array
    {
        return [
            'pages_limit' => $limits['pages_limit'] ?? 5,
            'crawl_depth' => $limits['crawl_depth'] ?? 1,
            'lighthouse_pages' => $limits['lighthouse_pages'] ?? 1,
            'audits_per_day' => $limits['audits_per_day'] ?? 10,
            'pdf_export' => $limits['pdf_export'] ?? false,
            'white_label' => $limits['white_label'] ?? false,
            'custom_domain' => $limits['custom_domain'] ?? false,
            'widget' => $limits['widget'] ?? false,
            'seats' => $limits['seats'] ?? 1,
        ];
    }

    protected function getSortOrder(string $key): int
    {
        return match($key) {
            'free' => 1,
            'pro' => 2,
            'agency' => 3,
            default => 99,
        };
    }
}

<?php

namespace Database\Seeders;

use App\Models\CrawlProvider;
use Illuminate\Database\Seeder;

class CrawlProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Google PageSpeed Insights',
                'code' => 'google_psi',
                'category' => 'speed',
                'is_active' => true,
                'settings_schema_json' => [
                    'api_key' => ['type' => 'string', 'required' => true, 'label' => 'API Key'],
                ],
                'cost_model_json' => [
                    'unit_cost' => 0,
                    'unit_name' => 'requests',
                    'notes' => 'Free but quota-limited',
                ],
            ],
            [
                'name' => 'HTTP Basic Crawler',
                'code' => 'http_basic',
                'category' => 'crawl',
                'is_active' => true,
                'settings_schema_json' => null,
                'cost_model_json' => [
                    'unit_cost' => 0,
                    'unit_name' => 'requests',
                    'notes' => 'Free (server resources only)',
                ],
            ],
            [
                'name' => 'DataForSEO',
                'code' => 'dataforseo',
                'category' => 'backlinks',
                'is_active' => true,
                'settings_schema_json' => [
                    'login' => ['type' => 'string', 'required' => true, 'label' => 'Login'],
                    'password' => ['type' => 'string', 'required' => true, 'label' => 'Password'],
                ],
                'cost_model_json' => [
                    'unit_cost' => 0.001,
                    'unit_name' => 'rows',
                    'notes' => 'Charged per row fetched',
                ],
            ],
            [
                'name' => 'GSC Position Fallback',
                'code' => 'gsc_fallback',
                'category' => 'serp',
                'is_active' => true,
                'settings_schema_json' => null,
                'cost_model_json' => [
                    'unit_cost' => 0,
                    'unit_name' => 'keywords',
                    'notes' => 'Free - uses Google Search Console data (approximation)',
                ],
            ],
        ];

        foreach ($providers as $provider) {
            CrawlProvider::updateOrCreate(
                ['code' => $provider['code']],
                $provider
            );
        }
    }
}

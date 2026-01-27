<?php

namespace App\Services\Crawl;

use App\Models\Domain;
use App\Models\User;
use App\Models\CrawlProvider;
use App\Models\UserProviderSetting;
use App\Models\DomainProviderPreference;
use App\Models\CrawlCostLog;
use App\Services\Crawl\Drivers\CrawlDriverInterface;
use App\Services\Crawl\Drivers\GooglePsiDriver;
use App\Services\Crawl\Drivers\HttpBasicDriver;
use App\Services\Crawl\Drivers\BacklinksProviderDriver;
use App\Services\Crawl\Drivers\GscPositionFallbackDriver;
use Illuminate\Support\Facades\Log;

class CrawlManager
{
    protected User $user;
    protected Domain $domain;

    public function __construct(User $user, Domain $domain)
    {
        $this->user = $user;
        $this->domain = $domain;
    }

    /**
     * Resolve provider for a task type
     */
    public function resolveProvider(string $taskType): ?CrawlProvider
    {
        // 1. Check domain preference
        $preference = DomainProviderPreference::where('domain_id', $this->domain->id)
            ->where('task_type', $taskType)
            ->first();

        if ($preference) {
            $provider = CrawlProvider::where('code', $preference->provider_code)
                ->where('is_active', true)
                ->first();

            if ($provider && $this->isProviderConfigured($provider)) {
                return $provider;
            }

            // Try fallbacks
            $fallbacks = $preference->fallback_codes_json ?? [];
            foreach ($fallbacks as $fallbackCode) {
                $fallbackProvider = CrawlProvider::where('code', $fallbackCode)
                    ->where('is_active', true)
                    ->first();

                if ($fallbackProvider && $this->isProviderConfigured($fallbackProvider)) {
                    return $fallbackProvider;
                }
            }
        }

        // 2. Check user default (future: user_provider_settings with is_default flag)
        // For now, skip to system default

        // 3. System default (by category)
        $category = explode('.', $taskType)[0];
        $provider = CrawlProvider::where('category', $category)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($provider && $this->isProviderConfigured($provider)) {
            return $provider;
        }

        return null;
    }

    /**
     * Check if provider is configured for user
     */
    protected function isProviderConfigured(CrawlProvider $provider): bool
    {
        $userSetting = UserProviderSetting::where('user_id', $this->user->id)
            ->where('provider_code', $provider->code)
            ->where('is_enabled', true)
            ->first();

        if (!$userSetting) {
            return false;
        }

        // Validate settings with driver
        $driver = $this->getDriver($provider, $userSetting->settings_json ?? []);
        $validation = $driver->validateSettings($userSetting->settings_json ?? []);

        return $validation['ok'] ?? false;
    }

    /**
     * Get driver instance
     */
    protected function getDriver(CrawlProvider $provider, array $settings): CrawlDriverInterface
    {
        return match($provider->code) {
            'google_psi' => new GooglePsiDriver($settings),
            'http_basic' => new HttpBasicDriver($settings),
            'dataforseo' => new BacklinksProviderDriver($settings),
            'gsc_fallback' => new GscPositionFallbackDriver(array_merge($settings, ['domain_id' => $this->domain->id])),
            default => throw new \Exception("Unknown provider: {$provider->code}"),
        };
    }

    /**
     * Run speed check (PageSpeed)
     */
    public function runSpeedCheck(string $url, string $strategy = 'mobile', array $context = []): array
    {
        $taskType = 'speed.pagespeed';
        $provider = $this->resolveProvider($taskType);

        if (!$provider) {
            throw new \Exception("No configured provider for {$taskType}");
        }

        $userSetting = UserProviderSetting::where('user_id', $this->user->id)
            ->where('provider_code', $provider->code)
            ->first();

        $driver = $this->getDriver($provider, $userSetting->settings_json ?? []);

        // Estimate cost
        $estimate = $driver->estimateCost(['url' => $url, 'strategy' => $strategy]);

        // Execute
        $result = $driver->execute(['url' => $url, 'strategy' => $strategy]);

        // Log cost
        $this->logCost($taskType, $provider->code, $estimate, $context);

        return $result;
    }

    /**
     * Run HTTP basic crawl
     */
    public function runHttpBasicCrawl(string $url, bool $extractMeta = false, array $context = []): array
    {
        $taskType = 'crawl.http_basic';
        $provider = $this->resolveProvider($taskType);

        if (!$provider) {
            throw new \Exception("No configured provider for {$taskType}");
        }

        $userSetting = UserProviderSetting::where('user_id', $this->user->id)
            ->where('provider_code', $provider->code)
            ->first();

        $driver = $this->getDriver($provider, $userSetting->settings_json ?? []);

        // Estimate cost
        $estimate = $driver->estimateCost(['url' => $url, 'extract_meta' => $extractMeta]);

        // Execute
        $result = $driver->execute(['url' => $url, 'extract_meta' => $extractMeta]);

        // Log cost
        $this->logCost($taskType, $provider->code, $estimate, $context);

        return $result;
    }

    /**
     * Run backlinks fetch
     */
    public function runBacklinksFetch(string $host, int $limit = 1000, int $offset = 0, array $context = []): array
    {
        $taskType = 'backlinks.provider';
        $provider = $this->resolveProvider($taskType);

        if (!$provider) {
            throw new \Exception("No configured provider for {$taskType}");
        }

        $userSetting = UserProviderSetting::where('user_id', $this->user->id)
            ->where('provider_code', $provider->code)
            ->first();

        $driver = $this->getDriver($provider, $userSetting->settings_json ?? []);

        // Estimate cost
        $estimate = $driver->estimateCost(['host' => $host, 'limit' => $limit, 'offset' => $offset]);

        // Execute
        $result = $driver->execute(['host' => $host, 'limit' => $limit, 'offset' => $offset]);

        // Log cost (use actual rows fetched)
        $actualUnits = $result['rows_fetched'] ?? $limit;
        $estimate['units'] = $actualUnits;
        $this->logCost($taskType, $provider->code, $estimate, $context);

        return $result;
    }

    /**
     * Run SERP rank check
     */
    public function runSerpRankCheck(array $keywords, array $context = []): array
    {
        $taskType = 'serp.rank_check';
        $provider = $this->resolveProvider($taskType);

        if (!$provider) {
            // Fallback to GSC if no provider configured
            $provider = CrawlProvider::where('code', 'gsc_fallback')->first();
            if (!$provider) {
                throw new \Exception("No configured provider for {$taskType}");
            }
        }

        $userSetting = UserProviderSetting::where('user_id', $this->user->id)
            ->where('provider_code', $provider->code)
            ->first();

        $settings = array_merge($userSetting->settings_json ?? [], ['domain_id' => $this->domain->id]);
        $driver = $this->getDriver($provider, $settings);

        // Execute for each keyword
        $results = [];
        $totalCostCents = 0;

        foreach ($keywords as $keywordData) {
            $estimate = $driver->estimateCost($keywordData);
            $result = $driver->execute($keywordData);
            
            $totalCostCents += $estimate['cents'] ?? 0;
            $results[] = $result;
        }

        // Log cost (total for all keywords)
        if ($totalCostCents > 0 || $provider->code === 'gsc_fallback') {
            $this->logCost($taskType, $provider->code, [
                'units' => count($keywords),
                'unit_name' => 'keywords',
                'cents' => $totalCostCents,
            ], $context);
        }

        return [
            'success' => true,
            'provider' => $provider->code,
            'results' => $results,
        ];
    }

    /**
     * Log cost
     */
    protected function logCost(string $taskType, string $providerCode, array $estimate, array $context = []): void
    {
        CrawlCostLog::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'task_type' => $taskType,
            'provider_code' => $providerCode,
            'units' => $estimate['units'] ?? 0,
            'unit_name' => $estimate['unit_name'] ?? 'requests',
            'estimated_cost_cents' => $estimate['cents'] ?? 0,
            'context_json' => $context,
            'created_at' => now(),
        ]);
    }
}



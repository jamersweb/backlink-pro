<?php

namespace App\Services;

use App\Models\Proxy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyHealthCheckService
{
    /**
     * Check health of a single proxy
     */
    public static function checkProxy(Proxy $proxy): bool
    {
        try {
            $testUrl = 'https://httpbin.org/ip'; // Simple test endpoint
            $timeout = 10; // seconds

            $response = Http::timeout($timeout)
                ->withOptions([
                    'proxy' => $proxy->url,
                ])
                ->get($testUrl);

            if ($response->successful()) {
                // Proxy is healthy, reset error count if it was low
                if ($proxy->error_count > 0 && $proxy->error_count < 5) {
                    $proxy->resetErrors();
                }
                
                // Reactivate if it was blacklisted but now works
                if ($proxy->status === Proxy::STATUS_BLACKLISTED) {
                    $proxy->update([
                        'status' => Proxy::STATUS_ACTIVE,
                        'error_count' => 0,
                    ]);
                }
                
                Log::info("Proxy health check passed", [
                    'proxy_id' => $proxy->id,
                    'host' => $proxy->host,
                ]);
                
                return true;
            } else {
                self::markProxyUnhealthy($proxy, "HTTP {$response->status()}");
                return false;
            }
        } catch (\Exception $e) {
            self::markProxyUnhealthy($proxy, $e->getMessage());
            return false;
        }
    }

    /**
     * Mark proxy as unhealthy
     */
    protected static function markProxyUnhealthy(Proxy $proxy, string $reason): void
    {
        $proxy->incrementError();
        
        Log::warning("Proxy health check failed", [
            'proxy_id' => $proxy->id,
            'host' => $proxy->host,
            'error_count' => $proxy->error_count,
            'reason' => $reason,
        ]);

        // Auto-blacklist after 10 errors
        if ($proxy->error_count >= 10) {
            $proxy->update(['status' => Proxy::STATUS_BLACKLISTED]);
            Log::warning("Proxy auto-blacklisted due to high error count", [
                'proxy_id' => $proxy->id,
                'host' => $proxy->host,
            ]);
        }
    }

    /**
     * Check health of all active proxies
     */
    public static function checkAllActiveProxies(): array
    {
        $proxies = Proxy::where('status', Proxy::STATUS_ACTIVE)->get();
        $results = [
            'checked' => 0,
            'healthy' => 0,
            'unhealthy' => 0,
        ];

        foreach ($proxies as $proxy) {
            $results['checked']++;
            if (self::checkProxy($proxy)) {
                $results['healthy']++;
            } else {
                $results['unhealthy']++;
            }
        }

        Log::info("Proxy health check completed", $results);
        return $results;
    }

    /**
     * Check health of proxies with recent errors
     */
    public static function checkUnhealthyProxies(): array
    {
        $proxies = Proxy::where('status', Proxy::STATUS_ACTIVE)
            ->where('error_count', '>', 0)
            ->where(function($q) {
                $q->whereNull('last_error_at')
                  ->orWhere('last_error_at', '<', now()->subHours(1)); // Check again after 1 hour
            })
            ->get();

        $results = [
            'checked' => 0,
            'recovered' => 0,
            'still_unhealthy' => 0,
        ];

        foreach ($proxies as $proxy) {
            $results['checked']++;
            $wasUnhealthy = $proxy->error_count > 0;
            
            if (self::checkProxy($proxy)) {
                if ($wasUnhealthy) {
                    $results['recovered']++;
                }
            } else {
                $results['still_unhealthy']++;
            }
        }

        return $results;
    }
}



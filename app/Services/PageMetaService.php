<?php

namespace App\Services;

use App\Models\PageMeta;
use Illuminate\Support\Facades\Cache;

class PageMetaService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected int $cacheDuration = 3600;

    /**
     * Get page meta by key with caching
     */
    public function getByKey(string $key): ?array
    {
        return Cache::remember("page_meta.{$key}", $this->cacheDuration, function () use ($key) {
            $pageMeta = PageMeta::where('page_key', $key)->where('is_active', true)->first();
            return $pageMeta ? $pageMeta->getFormattedMeta() : null;
        });
    }

    /**
     * Get page meta by route name
     */
    public function getByRoute(string $routeName): ?array
    {
        return Cache::remember("page_meta.route.{$routeName}", $this->cacheDuration, function () use ($routeName) {
            $pageMeta = PageMeta::where('route_name', $routeName)->where('is_active', true)->first();
            return $pageMeta ? $pageMeta->getFormattedMeta() : null;
        });
    }

    /**
     * Get page meta with fallback to defaults
     */
    public function getMeta(string $key, array $defaults = []): array
    {
        $meta = $this->getByKey($key);
        
        if (!$meta) {
            return $defaults;
        }

        // Merge with defaults for any missing values
        return array_merge($defaults, array_filter($meta, fn($v) => $v !== null && $v !== ''));
    }

    /**
     * Clear cache for a specific page
     */
    public function clearCache(string $key): void
    {
        Cache::forget("page_meta.{$key}");
    }

    /**
     * Clear all page meta cache
     */
    public function clearAllCache(): void
    {
        $pages = PageMeta::all();
        foreach ($pages as $page) {
            Cache::forget("page_meta.{$page->page_key}");
            if ($page->route_name) {
                Cache::forget("page_meta.route.{$page->route_name}");
            }
        }
    }

    /**
     * Get all active page metas (for sitemap generation, etc.)
     */
    public function getAllActive(): \Illuminate\Support\Collection
    {
        return Cache::remember('page_metas.all_active', $this->cacheDuration, function () {
            return PageMeta::where('is_active', true)->get();
        });
    }
}

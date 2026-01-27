<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class MarketingSitemapController extends Controller
{
    public function index()
    {
        // Cache key includes counts for cache invalidation when routes change
        $staticCount = count(config('marketing_routes.static', []));
        $dynamicCount = 0;
        foreach (config('marketing_routes.dynamic', []) as $def) {
            $items = config($def['source'], []);
            $dynamicCount += count($items);
        }
        $cacheKey = "marketing:sitemap:v1:static{$staticCount}:dynamic{$dynamicCount}";

        $xml = Cache::remember($cacheKey, 21600, function () {
            return $this->generateSitemap();
        });

        return Response::make($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function generateSitemap(): string
    {
        $base = rtrim(config('marketing_site.urls.app_url'), '/');
        $now = now()->toDateString();
        $static = config('marketing_routes.static', []);
        $dynamic = config('marketing_routes.dynamic', []);

        $urls = collect($static)->map(function ($route) use ($base, $now) {
            return [
                'loc' => $base . $route['path'],
                'priority' => $route['priority'] ?? null,
                'changefreq' => $route['changefreq'] ?? null,
                'lastmod' => $now,
            ];
        });

        // Process dynamic routes
        foreach ($dynamic as $def) {
            $items = config($def['source'], []);
            $slugKey = $def['slugKey'] ?? 'slug';
            $basePath = $def['base'] ?? '/';
            $priority = $def['priority'] ?? 0.5;
            $changefreq = $def['changefreq'] ?? 'monthly';

            foreach ($items as $item) {
                if (!isset($item[$slugKey])) {
                    continue;
                }
                $slug = $item[$slugKey];
                $urls->push([
                    'loc' => $base . $basePath . $slug,
                    'priority' => $priority,
                    'changefreq' => $changefreq,
                    'lastmod' => $now,
                ]);
            }
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            if ($url['changefreq']) {
                $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            }
            if ($url['priority'] !== null) {
                $xml .= "    <priority>{$url['priority']}</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}

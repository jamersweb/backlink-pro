<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Edge/Proxy (SEO-safe) mode: Worker calls /edge/meta and /edge/ping.
 * Only active when Feature::enabled('edge_proxy'). Routes registered conditionally in web.php.
 */
class EdgeProxyController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $host = $this->resolveHost($request);
        if (!$host) {
            return response()->json(['ok' => false, 'message' => 'Host required'], 422);
        }

        $domain = Domain::where('host', $host)->first();
        if (!$domain) {
            return response()->json(['ok' => false, 'message' => 'Domain not found'], 404);
        }

        $connector = DomainMetaConnector::where('domain_id', $domain->id)->where('type', 'edge_proxy')->first();
        if (!$connector) {
            return response()->json(['ok' => false, 'message' => 'Edge connector not configured'], 404);
        }

        $providedToken = $request->bearerToken();
        $expectedToken = is_array($connector->auth_json) ? ($connector->auth_json['edge_token'] ?? null) : null;
        if (!$providedToken || !$expectedToken || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            return response()->json(['ok' => false, 'message' => 'Invalid token'], 401);
        }

        $authJson = $connector->auth_json ?? [];
        $authJson['last_seen_at'] = now()->toIso8601String();
        $connector->update([
            'status' => DomainMetaConnector::STATUS_CONNECTED,
            'last_tested_at' => now(),
            'last_error' => null,
            'auth_json' => $authJson,
        ]);

        return response()->json(['ok' => true]);
    }

    public function meta(Request $request): JsonResponse
    {
        $host = $this->resolveHost($request);
        if (!$host) {
            return response()->json(['meta' => null], 422);
        }

        $path = $request->query('path');
        if ($path === null || $path === '') {
            $path = parse_url($request->fullUrl(), PHP_URL_PATH) ?? '/';
        }
        $path = '/' . trim((string) $path, '/');
        if ($path === '') {
            $path = '/';
        }
        $pathNormalized = rtrim($path, '/') ?: '/';

        $domain = Domain::where('host', $host)->first();
        if (!$domain) {
            return response()->json(['meta' => null], 404);
        }

        $connector = DomainMetaConnector::where('domain_id', $domain->id)->where('type', 'edge_proxy')->first();
        if (!$connector) {
            return response()->json(['meta' => null], 404);
        }

        $providedToken = $request->bearerToken();
        $expectedToken = is_array($connector->auth_json) ? ($connector->auth_json['edge_token'] ?? null) : null;
        if (!$providedToken || !$expectedToken || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            return response()->json(['meta' => null], 401);
        }

        $page = $domain->metaPages()
            ->where(function ($q) use ($pathNormalized) {
                $q->where('path', $pathNormalized)
                    ->orWhere('path', $pathNormalized . '/')
                    ->orWhere('path', rtrim($pathNormalized, '/'));
            })
            ->first();

        if (!$page) {
            $page = $domain->metaPages()->whereIn('path', ['/', ''])->first();
        }

        if (!$page) {
            $meta = [
                'title' => '',
                'description' => '',
                'og_title' => '',
                'og_description' => '',
                'og_image' => '',
                'canonical' => '',
                'robots' => 'index,follow',
            ];
        } else {
            $raw = $page->meta_published_json ?? $page->meta_current_json ?? [];
            $meta = [
                'title' => $raw['title'] ?? '',
                'description' => $raw['description'] ?? '',
                'og_title' => $raw['og_title'] ?? '',
                'og_description' => $raw['og_description'] ?? '',
                'og_image' => $raw['og_image'] ?? '',
                'canonical' => $raw['canonical'] ?? '',
                'robots' => $raw['robots'] ?? 'index,follow',
            ];
        }

        $cacheTtl = (int) (is_array($connector->auth_json) ? ($connector->auth_json['cache_ttl'] ?? 300) : 300);
        $cacheTtl = max(0, min(86400, $cacheTtl));

        return response()->json(['meta' => $meta])
            ->header('Cache-Control', 'public, max-age=' . $cacheTtl);
    }

    private function resolveHost(Request $request): ?string
    {
        $host = $request->query('host') ?? $request->input('host');
        if ($host) {
            return strtolower(trim((string) $host));
        }
        return strtolower(trim($request->getHost()));
    }
}

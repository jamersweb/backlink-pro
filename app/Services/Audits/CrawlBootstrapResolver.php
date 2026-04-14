<?php

namespace App\Services\Audits;

use Illuminate\Support\Facades\Http;

class CrawlBootstrapResolver
{
    public static function resolve(string $input): array
    {
        $normalized = self::normalizeBaseUrl($input);
        if (!$normalized) {
            return [
                'success' => false,
                'working_base_url' => null,
                'attempts' => [],
                'error' => 'Invalid start URL for crawl bootstrap.',
            ];
        }

        $candidates = self::candidateBaseUrls($normalized);
        $attempts = [];

        foreach ($candidates as $candidate) {
            $probe = self::probe($candidate);
            $attempts[] = $probe;
            if ($probe['success']) {
                return [
                    'success' => true,
                    'working_base_url' => $candidate,
                    'attempts' => $attempts,
                    'error' => null,
                ];
            }
        }

        return [
            'success' => false,
            'working_base_url' => null,
            'attempts' => $attempts,
            'error' => 'Unable to reach domain on both primary and fallback host variants.',
        ];
    }

    public static function normalizeBaseUrl(string $input): ?string
    {
        $url = trim($input);
        if ($url === '') {
            return null;
        }

        $hasScheme = preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $url) === 1;
        if ($hasScheme && !preg_match('/^https?:\/\//i', $url)) {
            return null;
        }
        if (!$hasScheme) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        if (!preg_match('/^[a-z0-9.-]+$/', $host)) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $normalized = "{$scheme}://{$host}{$port}";

        if (!filter_var($normalized, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $normalized;
    }

    public static function candidateBaseUrls(string $normalizedBaseUrl): array
    {
        $parts = parse_url($normalizedBaseUrl);
        if (!$parts || empty($parts['host'])) {
            return [$normalizedBaseUrl];
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        $alternateHost = str_starts_with($host, 'www.')
            ? substr($host, 4)
            : 'www.' . $host;

        $primary = "{$scheme}://{$host}{$port}";
        $alternate = "{$scheme}://{$alternateHost}{$port}";

        return array_values(array_unique([$primary, $alternate]));
    }

    protected static function probe(string $baseUrl): array
    {
        try {
            $response = Http::timeout(12)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                ])
                ->get($baseUrl . '/');

            return [
                'url' => $baseUrl,
                'success' => true,
                'status' => $response->status(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'url' => $baseUrl,
                'success' => false,
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}


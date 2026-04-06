<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SeoAuditHttp
{
    /**
     * Standard crawl GET request; applies forms-auth session cookies when present.
     */
    public static function crawlGet(Audit $audit, string $url): PendingRequest
    {
        $req = Http::timeout(20)
            ->connectTimeout(8)
            ->withUserAgent('BacklinkProBot/1.0')
            ->withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => true,
                    'referer' => true,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => true,
                ],
            ]);

        $cookies = FormsAuthService::simpleCookiesForHost($audit);
        $host = parse_url($audit->normalized_url, PHP_URL_HOST);
        if ($cookies !== [] && is_string($host) && $host !== '') {
            $req = $req->withCookies($cookies, $host);
        }

        return $req;
    }

    /**
     * Browser-like headers for quick single-page audits.
     */
    public static function browserLikeGet(Audit $audit, string $url): PendingRequest
    {
        $req = Http::timeout(25)
            ->retry(2, 1000)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => true,
                    'referer' => true,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => true,
                ],
            ]);

        $cookies = FormsAuthService::simpleCookiesForHost($audit);
        $host = parse_url($audit->normalized_url, PHP_URL_HOST);
        if ($cookies !== [] && is_string($host) && $host !== '') {
            $req = $req->withCookies($cookies, $host);
        }

        return $req;
    }
}

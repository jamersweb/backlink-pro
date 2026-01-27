<?php

namespace App\Services\Crawl\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class HttpBasicDriver implements CrawlDriverInterface
{
    protected array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function supports(string $taskType): bool
    {
        return $taskType === 'crawl.http_basic';
    }

    public function validateSettings(array $settings): array
    {
        // HttpBasic doesn't require settings
        return ['ok' => true, 'message' => 'No configuration required'];
    }

    public function execute(array $taskPayload): array
    {
        $url = $taskPayload['url'] ?? null;
        $extractMeta = $taskPayload['extract_meta'] ?? false;

        if (!$url) {
            throw new \InvalidArgumentException('URL is required');
        }

        try {
            // Follow redirects
            $response = Http::timeout(20)
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($url);

            $statusCode = $response->status();
            $finalUrl = $response->effectiveUri() ?? $url;
            $headers = $response->headers();
            $contentType = $headers['content-type'][0] ?? null;

            $result = [
                'success' => true,
                'url' => $url,
                'final_url' => $finalUrl,
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'redirect_chain' => $response->transferStats?->getHandlerStats()['redirect_count'] ?? 0,
            ];

            // Extract basic meta if HTML
            if ($extractMeta && $statusCode === 200 && str_contains($contentType ?? '', 'text/html')) {
                $html = $response->body();
                $meta = $this->extractMeta($html);
                $result = array_merge($result, $meta);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('HttpBasic driver error', ['error' => $e->getMessage(), 'url' => $url]);
            throw $e;
        }
    }

    public function estimateCost(array $taskPayload): array
    {
        // HttpBasic is essentially free (server resources only)
        return [
            'units' => 1.0,
            'unit_name' => 'requests',
            'cents' => 0,
        ];
    }

    protected function extractMeta(string $html): array
    {
        $meta = [
            'title' => null,
            'meta_description' => null,
            'canonical' => null,
            'robots_meta' => null,
        ];

        try {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // Title
            $titleNodes = $xpath->query('//title');
            if ($titleNodes->length > 0) {
                $meta['title'] = trim($titleNodes->item(0)->textContent);
            }

            // Meta description
            $descNodes = $xpath->query('//meta[@name="description"]');
            if ($descNodes->length > 0) {
                $meta['meta_description'] = $descNodes->item(0)->getAttribute('content');
            }

            // Canonical
            $canonicalNodes = $xpath->query('//link[@rel="canonical"]');
            if ($canonicalNodes->length > 0) {
                $meta['canonical'] = $canonicalNodes->item(0)->getAttribute('href');
            }

            // Robots meta
            $robotsNodes = $xpath->query('//meta[@name="robots"]');
            if ($robotsNodes->length > 0) {
                $meta['robots_meta'] = $robotsNodes->item(0)->getAttribute('content');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract meta', ['error' => $e->getMessage()]);
        }

        return $meta;
    }
}



<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditAsset;
use App\Models\AuditPage;
use App\Services\SeoAudit\UrlNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CollectAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public int $pageId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        $page = AuditPage::find($this->pageId);
        
        if (!$audit || !$page) {
            Log::warning("Audit or page not found", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
            ]);
            return;
        }

        try {
            // Fetch page HTML
            $response = Http::timeout(20)->get($page->url);
            if (!$response->successful()) {
                Log::warning("Failed to fetch page for asset collection: {$page->url}");
                return;
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            
            $baseHost = UrlNormalizer::extractHost($audit->normalized_url);
            $assets = [];

            // Extract images
            $this->extractImages($xpath, $page->url, $baseHost, $assets);
            
            // Extract scripts
            $this->extractScripts($xpath, $page->url, $baseHost, $assets);
            
            // Extract stylesheets
            $this->extractStylesheets($xpath, $page->url, $baseHost, $assets);
            
            // Extract fonts
            $this->extractFonts($xpath, $page->url, $baseHost, $assets);
            
            // Extract other assets (preload, etc.)
            $this->extractOtherAssets($xpath, $page->url, $baseHost, $assets);

            // Limit to 100 assets per page
            $assets = array_slice($assets, 0, 100);

            // Collect sizes for each asset
            foreach ($assets as $asset) {
                $this->collectAssetSize($audit, $page, $asset);
            }

        } catch (\Exception $e) {
            Log::error("CollectAssetsJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Extract image assets
     */
    protected function extractImages(\DOMXPath $xpath, string $baseUrl, string $baseHost, array &$assets): void
    {
        try {
            // Regular img src
            $images = $xpath->query('//img[@src]');
            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                if ($src) {
                    $absoluteUrl = $this->resolveUrl($src, $baseUrl);
                    if ($absoluteUrl) {
                        $assets[] = [
                            'url' => $absoluteUrl,
                            'type' => AuditAsset::TYPE_IMG,
                            'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                        ];
                    }
                }
            }

            // srcset
            $imagesSrcset = $xpath->query('//img[@srcset]');
            foreach ($imagesSrcset as $img) {
                $srcset = $img->getAttribute('srcset');
                if ($srcset) {
                    preg_match_all('/\s*([^\s,]+)/', $srcset, $matches);
                    if (!empty($matches[1])) {
                        $src = $matches[1][0];
                        $absoluteUrl = $this->resolveUrl($src, $baseUrl);
                        if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                            $assets[] = [
                                'url' => $absoluteUrl,
                                'type' => AuditAsset::TYPE_IMG,
                                'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                            ];
                        }
                    }
                }
            }

            // source srcset
            $sources = $xpath->query('//source[@srcset]');
            foreach ($sources as $source) {
                $srcset = $source->getAttribute('srcset');
                if ($srcset) {
                    preg_match_all('/\s*([^\s,]+)/', $srcset, $matches);
                    if (!empty($matches[1])) {
                        $src = $matches[1][0];
                        $absoluteUrl = $this->resolveUrl($src, $baseUrl);
                        if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                            $assets[] = [
                                'url' => $absoluteUrl,
                                'type' => AuditAsset::TYPE_IMG,
                                'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error extracting images: " . $e->getMessage());
        }
    }

    /**
     * Extract script assets
     */
    protected function extractScripts(\DOMXPath $xpath, string $baseUrl, string $baseHost, array &$assets): void
    {
        try {
            $scripts = $xpath->query('//script[@src]');
            foreach ($scripts as $script) {
                $src = $script->getAttribute('src');
                if ($src) {
                    $absoluteUrl = $this->resolveUrl($src, $baseUrl);
                    if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                        $assets[] = [
                            'url' => $absoluteUrl,
                            'type' => AuditAsset::TYPE_JS,
                            'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error extracting scripts: " . $e->getMessage());
        }
    }

    /**
     * Extract stylesheet assets
     */
    protected function extractStylesheets(\DOMXPath $xpath, string $baseUrl, string $baseHost, array &$assets): void
    {
        try {
            $links = $xpath->query('//link[@rel="stylesheet"]');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($href) {
                    $absoluteUrl = $this->resolveUrl($href, $baseUrl);
                    if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                        $assets[] = [
                            'url' => $absoluteUrl,
                            'type' => AuditAsset::TYPE_CSS,
                            'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error extracting stylesheets: " . $e->getMessage());
        }
    }

    /**
     * Extract font assets
     */
    protected function extractFonts(\DOMXPath $xpath, string $baseUrl, string $baseHost, array &$assets): void
    {
        try {
            // Preload fonts
            $links = $xpath->query('//link[@rel="preload" and @as="font"]');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($href) {
                    $absoluteUrl = $this->resolveUrl($href, $baseUrl);
                    if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                        $assets[] = [
                            'url' => $absoluteUrl,
                            'type' => AuditAsset::TYPE_FONT,
                            'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                        ];
                    }
                }
            }

            // @font-face in CSS (simplified - would need CSS parsing)
            // For now, skip this as it requires CSS parsing
        } catch (\Exception $e) {
            Log::debug("Error extracting fonts: " . $e->getMessage());
        }
    }

    /**
     * Extract other assets (preload, etc.)
     */
    protected function extractOtherAssets(\DOMXPath $xpath, string $baseUrl, string $baseHost, array &$assets): void
    {
        try {
            $links = $xpath->query('//link[@rel="preload"]');
            foreach ($links as $link) {
                $as = $link->getAttribute('as');
                if ($as && !in_array($as, ['font', 'style', 'script'])) {
                    $href = $link->getAttribute('href');
                    if ($href) {
                        $absoluteUrl = $this->resolveUrl($href, $baseUrl);
                        if ($absoluteUrl && !$this->assetExists($assets, $absoluteUrl)) {
                            $assets[] = [
                                'url' => $absoluteUrl,
                                'type' => AuditAsset::TYPE_OTHER,
                                'is_third_party' => !UrlNormalizer::isInternal($absoluteUrl, $baseHost),
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error extracting other assets: " . $e->getMessage());
        }
    }

    /**
     * Collect asset size
     */
    protected function collectAssetSize(Audit $audit, AuditPage $page, array $asset): void
    {
        try {
            // Skip if asset already exists
            $existing = AuditAsset::where('audit_id', $audit->id)
                ->where('audit_page_id', $page->id)
                ->where('asset_url', $asset['url'])
                ->first();

            if ($existing) {
                return;
            }

            // Try HEAD request first
            $response = Http::timeout(10)->head($asset['url']);
            $sizeBytes = null;
            $contentType = null;
            $statusCode = $response->status();

            if ($response->successful()) {
                $contentLength = $response->header('Content-Length');
                if ($contentLength) {
                    $sizeBytes = (int) $contentLength;
                }
                $contentType = $response->header('Content-Type');
            }

            // If HEAD didn't work or no Content-Length, try GET with Range header
            if (!$sizeBytes && $statusCode < 400) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders(['Range' => 'bytes=0-0'])
                        ->get($asset['url']);
                    
                    $contentRange = $response->header('Content-Range');
                    if ($contentRange && preg_match('/bytes \d+-\d+\/(\d+)/', $contentRange, $matches)) {
                        $sizeBytes = (int) $matches[1];
                    } elseif ($response->successful()) {
                        // Fallback: use response body size
                        $sizeBytes = strlen($response->body());
                    }
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }

            // Skip if asset is too large (>20MB)
            if ($sizeBytes && $sizeBytes > 20 * 1024 * 1024) {
                return;
            }

            // Create asset record
            AuditAsset::create([
                'audit_id' => $audit->id,
                'audit_page_id' => $page->id,
                'page_url' => $page->url,
                'asset_url' => $asset['url'],
                'type' => $asset['type'],
                'size_bytes' => $sizeBytes,
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'is_third_party' => $asset['is_third_party'],
            ]);

        } catch (\Exception $e) {
            Log::debug("Error collecting asset size for {$asset['url']}: " . $e->getMessage());
        }
    }

    /**
     * Check if asset already exists in array
     */
    protected function assetExists(array $assets, string $url): bool
    {
        foreach ($assets as $asset) {
            if ($asset['url'] === $url) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve relative URL
     */
    protected function resolveUrl(string $relative, string $baseUrl): ?string
    {
        if (preg_match('/^https?:\/\//', $relative)) {
            return $relative;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (strpos($relative, '/') === 0) {
            return $scheme . '://' . $host . $relative;
        }

        $basePath = $parsed['path'] ?? '/';
        if (substr($basePath, -1) !== '/') {
            $basePath = dirname($basePath) . '/';
        }

        return $scheme . '://' . $host . $basePath . $relative;
    }
}

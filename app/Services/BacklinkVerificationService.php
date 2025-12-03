<?php

namespace App\Services;

use App\Models\Backlink;
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BacklinkVerificationService
{
    /**
     * Verify a backlink by checking if the URL contains the link or anchor text
     */
    public static function verify(Backlink $backlink): bool
    {
        try {
            // Fetch the page content
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($backlink->url);

            if (!$response->successful()) {
                self::markAsFailed($backlink, 'Failed to fetch page: HTTP ' . $response->status());
                return false;
            }

            $html = $response->body();
            $campaign = $backlink->campaign;

            // Check for the link or anchor text
            $isVerified = false;
            $verificationMethod = '';

            // Method 1: Check if campaign domain URL appears in the page
            if ($campaign && $campaign->web_url) {
                $domain = parse_url($campaign->web_url, PHP_URL_HOST);
                if ($domain && (stripos($html, $domain) !== false || stripos($html, $campaign->web_url) !== false)) {
                    $isVerified = true;
                    $verificationMethod = 'domain_found';
                }
            }

            // Method 2: Check if anchor text appears in links
            if (!$isVerified && $backlink->anchor_text) {
                // Look for anchor text in href attributes or link text
                $anchorTextEscaped = preg_quote($backlink->anchor_text, '/');
                if (preg_match('/<a[^>]*>.*?' . $anchorTextEscaped . '.*?<\/a>/is', $html)) {
                    $isVerified = true;
                    $verificationMethod = 'anchor_text_found';
                }
            }

            // Method 3: Check if keyword appears near link context
            if (!$isVerified && $backlink->keyword) {
                $keywordEscaped = preg_quote($backlink->keyword, '/');
                if (preg_match('/<a[^>]*href[^>]*>.*?' . $keywordEscaped . '.*?<\/a>/is', $html)) {
                    $isVerified = true;
                    $verificationMethod = 'keyword_found';
                }
            }

            if ($isVerified) {
                self::markAsVerified($backlink, $verificationMethod);
                return true;
            } else {
                self::markAsFailed($backlink, 'Link or anchor text not found on page');
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Backlink verification error', [
                'backlink_id' => $backlink->id,
                'url' => $backlink->url,
                'error' => $e->getMessage(),
            ]);
            
            self::markAsFailed($backlink, 'Verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark backlink as verified
     */
    protected static function markAsVerified(Backlink $backlink, string $method = ''): void
    {
        $backlink->update([
            'status' => Backlink::STATUS_VERIFIED,
            'verified_at' => now(),
            'error_message' => null,
        ]);

        // Log activity
        ActivityLogService::logBacklinkVerified($backlink);

        // Send notification
        NotificationService::notifyBacklinkVerified($backlink);
    }

    /**
     * Mark backlink as failed
     */
    protected static function markAsFailed(Backlink $backlink, string $reason = ''): void
    {
        $backlink->update([
            'status' => Backlink::STATUS_FAILED,
            'error_message' => $reason,
        ]);

        // Log activity
        ActivityLogService::logBacklinkFailed($backlink, $reason);

        // Send notification
        NotificationService::notifyBacklinkFailed($backlink, $reason);
    }
}



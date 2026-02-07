<?php

namespace App\Services\Analytics;

use App\Models\DwEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class EventTracker
{
    /**
     * Track an event
     */
    public function track(string $eventName, array $properties = [], array $context = [], string $source = 'app'): void
    {
        $user = Auth::user();
        $organization = $user?->currentOrganization ?? null;
        
        // Get anonymous ID from cookie or session
        $anonymousId = $this->getAnonymousId();

        DwEvent::create([
            'organization_id' => $organization?->id,
            'user_id' => $user?->id,
            'anonymous_id' => $anonymousId,
            'event_name' => $eventName,
            'event_time' => now(),
            'properties' => $properties,
            'context' => array_merge($this->getDefaultContext(), $context),
            'source' => $source,
        ]);
    }

    /**
     * Get or create anonymous ID
     */
    protected function getAnonymousId(): ?string
    {
        $cookieName = 'bp_anon_id';
        
        if (request()->hasCookie($cookieName)) {
            return request()->cookie($cookieName);
        }

        $anonymousId = bin2hex(random_bytes(16));
        
        // Set cookie for 1 year
        cookie()->queue($cookieName, $anonymousId, 525600);
        
        return $anonymousId;
    }

    /**
     * Get default context
     */
    protected function getDefaultContext(): array
    {
        return [
            'utm' => [
                'source' => request()->get('utm_source'),
                'medium' => request()->get('utm_medium'),
                'campaign' => request()->get('utm_campaign'),
                'term' => request()->get('utm_term'),
                'content' => request()->get('utm_content'),
            ],
            'referrer' => request()->header('referer'),
            'device' => $this->detectDevice(),
            'geo' => $this->detectGeo(),
        ];
    }

    /**
     * Detect device type
     */
    protected function detectDevice(): array
    {
        $userAgent = request()->userAgent();
        
        return [
            'type' => $this->isMobile($userAgent) ? 'mobile' : 'desktop',
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOS($userAgent),
        ];
    }

    /**
     * Check if mobile
     */
    protected function isMobile(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }
        
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent) > 0;
    }

    /**
     * Detect browser
     */
    protected function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }
        
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        
        return 'Unknown';
    }

    /**
     * Detect OS
     */
    protected function detectOS(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }
        
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false) return 'iOS';
        
        return 'Unknown';
    }

    /**
     * Detect geo (simplified - would use GeoIP in production)
     */
    protected function detectGeo(): array
    {
        // Simplified - would use GeoIP service
        return [
            'country' => null,
            'city' => null,
        ];
    }
}

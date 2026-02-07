<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\Referral;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $refCode = $request->query('ref');
        
        if ($refCode) {
            $affiliate = Affiliate::where('code', $refCode)
                ->where('status', Affiliate::STATUS_ACTIVE)
                ->first();

            if ($affiliate) {
                // Generate visitor ID (cookie-based)
                $visitorId = $request->cookie('bp_visitor_id') ?? bin2hex(random_bytes(32));
                
                // Check if referral already exists for this visitor
                $referral = Referral::where('affiliate_id', $affiliate->id)
                    ->where('visitor_id', $visitorId)
                    ->where('status', Referral::STATUS_CLICKED)
                    ->where('first_touch_at', '>', now()->subDays(30))
                    ->first();

                if (!$referral) {
                    // Create new referral
                    Referral::create([
                        'affiliate_id' => $affiliate->id,
                        'visitor_id' => $visitorId,
                        'first_touch_at' => now(),
                        'last_touch_at' => now(),
                        'utm' => $request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']),
                        'landing_page' => $request->fullUrl(),
                        'referrer_url' => $request->header('Referer'),
                        'status' => Referral::STATUS_CLICKED,
                    ]);
                } else {
                    // Update last touch
                    $referral->update([
                        'last_touch_at' => now(),
                    ]);
                }

                // Set cookie with affiliate ID (signed, expires in 30 days)
                $cookie = Cookie::make(
                    'bp_ref',
                    Crypt::encryptString($affiliate->id),
                    60 * 24 * 30 // 30 days
                );

                $response = $next($request);
                return $response->withCookie($cookie);
            }
        }

        return $next($request);
    }
}

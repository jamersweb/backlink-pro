<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    /**
     * Get proxy list with smart selection logic
     */
    public function index(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $country = $request->get('country');
        $preferCountry = $request->get('prefer_country', true); // Prefer country match
        
        // Start with active, healthy proxies (error_count < 3)
        $query = Proxy::where('status', Proxy::STATUS_ACTIVE)
            ->healthy(3); // Only proxies with < 3 errors

        // If country is specified and we prefer country matches
        if ($country && $preferCountry) {
            // First try to get proxies from the target country
            $countryProxies = (clone $query)->forCountry($country)
                ->orderBy('error_count', 'asc')
                ->orderBy('last_used_at', 'asc')
                ->get();

            // If we have country-specific proxies, return them
            if ($countryProxies->isNotEmpty()) {
                $proxies = $countryProxies;
            } else {
                // Fallback to any healthy proxy
                $proxies = $query->orderBy('error_count', 'asc')
                    ->orderBy('last_used_at', 'asc')
                    ->get();
            }
        } else {
            // No country preference, just get healthy proxies
            $proxies = $query->orderBy('error_count', 'asc')
                ->orderBy('last_used_at', 'asc')
                ->get();
        }

        // Rotate proxies: prefer least recently used
        $proxies = $proxies->sortBy([
            ['error_count', 'asc'],
            ['last_used_at', 'asc'],
        ])->values();

        $proxies = $proxies->map(function ($proxy) {
            // Mark proxy as used
            $proxy->markUsed();
            
            return [
                'id' => $proxy->id,
                'host' => $proxy->host,
                'port' => $proxy->port,
                'username' => $proxy->username,
                'password' => $proxy->password,
                'country' => $proxy->country,
                'type' => $proxy->type,
            ];
        });

        return response()->json([
            'proxies' => $proxies,
        ]);
    }
}


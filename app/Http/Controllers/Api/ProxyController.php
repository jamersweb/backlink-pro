<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    /**
     * Get proxy list
     */
    public function index(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $country = $request->get('country');
        
        $query = Proxy::where('status', Proxy::STATUS_ACTIVE)
            ->orderBy('error_count', 'asc')
            ->orderBy('last_used_at', 'asc');

        if ($country) {
            $query->where('country', $country);
        }

        $proxies = $query->get()->map(function ($proxy) {
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


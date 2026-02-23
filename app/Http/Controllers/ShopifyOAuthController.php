<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopifyOAuthController extends Controller
{
    /**
     * Start OAuth install: store state in session, redirect to Shopify authorize.
     */
    public function install(Request $request, Domain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        $shop = $request->query('shop');
        if (!$shop) {
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'Missing shop parameter. Provide your myshopify.com domain.');
        }

        $shop = $this->normalizeShop($shop);
        $state = Str::random(40);
        $request->session()->put('shopify_oauth_state', $state);
        $request->session()->put('shopify_oauth_domain_id', $domain->id);

        $redirectUri = route('domains.shopify.callback', $domain);
        $params = http_build_query([
            'client_id' => config('shopify.api_key'),
            'scope' => config('shopify.scopes'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        $url = 'https://' . $shop . '/admin/oauth/authorize?' . $params;

        return redirect()->away($url);
    }

    /**
     * OAuth callback: validate state and HMAC, exchange code for token, save connector.
     */
    public function callback(Request $request, Domain $domain): RedirectResponse
    {
        $state = $request->session()->get('shopify_oauth_state');
        $storedDomainId = $request->session()->get('shopify_oauth_domain_id');

        if (!$state || $storedDomainId != $domain->id || $request->query('state') !== $state) {
            $request->session()->forget(['shopify_oauth_state', 'shopify_oauth_domain_id']);
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'Invalid state. Please try connecting again.');
        }

        $code = $request->query('code');
        $shop = $this->normalizeShop($request->query('shop'));
        $hmac = $request->query('hmac');

        if (!$code || !$shop) {
            $request->session()->forget(['shopify_oauth_state', 'shopify_oauth_domain_id']);
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'Missing code or shop.');
        }

        $params = [
            'client_id' => config('shopify.api_key'),
            'client_secret' => config('shopify.api_secret'),
            'code' => $code,
        ];
        if (!static::verifyHmac($request->query())) {
            $request->session()->forget(['shopify_oauth_state', 'shopify_oauth_domain_id']);
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'HMAC verification failed.');
        }

        $response = Http::post('https://' . $shop . '/admin/oauth/access_token', $params);

        if (!$response->successful()) {
            $request->session()->forget(['shopify_oauth_state', 'shopify_oauth_domain_id']);
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'Could not get access token: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        if (!$accessToken) {
            return redirect()->route('domains.meta.index', $domain)
                ->with('error', 'No access token in response.');
        }

        $this->authorize('update', $domain);

        $connector = DomainMetaConnector::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'user_id' => Auth::id(),
                'type' => DomainMetaConnector::TYPE_SHOPIFY,
                'status' => DomainMetaConnector::STATUS_CONNECTED,
                'base_url' => 'https://' . $shop,
                'auth_json' => [
                    'shop' => $shop,
                    'access_token' => $accessToken,
                    'api_version' => config('shopify.api_version'),
                ],
                'last_tested_at' => now(),
                'last_error' => null,
            ]
        );

        $request->session()->forget(['shopify_oauth_state', 'shopify_oauth_domain_id']);

        return redirect()->route('domains.meta.index', $domain)
            ->with('success', 'Shopify connected successfully.');
    }

    private function normalizeShop(string $shop): string
    {
        $shop = strtolower(trim($shop));
        if (!str_ends_with($shop, '.myshopify.com')) {
            $shop = $shop . '.myshopify.com';
        }
        return $shop;
    }

    /**
     * Verify Shopify HMAC: build string from sorted params (excluding hmac/signature), then hash_equals.
     */
    public static function verifyHmac(array $queryParams): bool
    {
        $secret = config('shopify.api_secret');
        if (!$secret) {
            return false;
        }
        $all = $queryParams;
        unset($all['hmac'], $all['signature']);
        ksort($all);
        $str = http_build_query($all, '', '&', PHP_QUERY_RFC3986);
        $computed = hash_hmac('sha256', $str, $secret, true);
        $computedHex = bin2hex($computed);
        $provided = $queryParams['hmac'] ?? $queryParams['signature'] ?? '';
        return hash_equals($computedHex, $provided);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Services\Meta\Connectors\MetaConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DomainMetaConnectorController extends Controller
{
    /**
     * Connect or update connector
     */
    public function connectOrUpdate(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $types = ['wordpress', 'shopify', 'custom_js'];
        if (\App\Support\Feature::enabled('edge_proxy')) {
            $types[] = 'edge_proxy';
        }
        $rules = [
            'type' => 'required|in:' . implode(',', $types),
            'base_url' => 'required_if:type,wordpress|nullable|url',
            'api_token' => 'required_if:type,wordpress|nullable|string',
            'shop_domain' => 'required_if:type,shopify|nullable|string',
            'admin_access_token' => 'required_if:type,shopify|nullable|string',
            'api_version' => 'nullable|string',
        ];
        if (\App\Support\Feature::enabled('edge_proxy')) {
            $rules['cache_ttl'] = 'nullable|integer|min:0|max:86400';
        }
        $validated = $request->validate($rules);

        $type = $validated['type'];
        $authJson = [];

        if ($type === 'wordpress') {
            $authJson = [
                'token' => $validated['api_token'],
            ];
        } elseif ($type === 'shopify') {
            $authJson = [
                'shop_domain' => $validated['shop_domain'],
                'admin_access_token' => $validated['admin_access_token'],
                'api_version' => $validated['api_version'] ?? '2024-01',
            ];
        } elseif ($type === 'custom_js') {
            if (!$domain->meta_snippet_key) {
                $domain->update([
                    'meta_snippet_key' => Str::random(40),
                ]);
            }
        } elseif ($type === 'edge_proxy') {
            $existing = DomainMetaConnector::where('domain_id', $domain->id)->where('type', 'edge_proxy')->first();
            $authJson = $existing && !empty($existing->auth_json['edge_token'] ?? null)
                ? $existing->auth_json
                : ['edge_token' => Str::random(64)];
            $authJson['cache_ttl'] = (int) ($validated['cache_ttl'] ?? 300);
            $authJson['cache_ttl'] = max(0, min(86400, $authJson['cache_ttl']));
        }

        $connector = DomainMetaConnector::updateOrCreate(
            [
                'domain_id' => $domain->id,
            ],
            [
                'user_id' => Auth::id(),
                'type' => $type,
                'status' => DomainMetaConnector::STATUS_DISCONNECTED, // Will be set to connected after test
                'base_url' => $validated['base_url'] ?? null,
                'auth_json' => $authJson,
            ]
        );

        return back()->with('success', 'Connector saved. Please test the connection.');
    }

    /**
     * Test connector connection
     */
    public function test(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $connector = $domain->metaConnector;
        if (!$connector) {
            return back()->with('error', 'No connector configured');
        }

        try {
            $metaConnector = MetaConnectorFactory::make($connector->type);
            $result = $metaConnector->testConnection($connector);

            $connector->update([
                'status' => $result['ok'] 
                    ? DomainMetaConnector::STATUS_CONNECTED 
                    : DomainMetaConnector::STATUS_ERROR,
                'last_tested_at' => now(),
                'last_error' => $result['ok'] ? null : $result['message'],
            ]);

            if ($result['ok']) {
                return back()->with('success', 'Connection successful!');
            } else {
                return back()->with('error', 'Connection failed: ' . $result['message']);
            }
        } catch (\Exception $e) {
            $connector->update([
                'status' => DomainMetaConnector::STATUS_ERROR,
                'last_tested_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect connector
     */
    public function disconnect(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $connector = $domain->metaConnector;
        if ($connector) {
            $connector->update([
                'status' => DomainMetaConnector::STATUS_DISCONNECTED,
                'auth_json' => null,
                'base_url' => null,
            ]);
        }

        return back()->with('success', 'Connector disconnected');
    }

    /**
     * Rotate edge_proxy token (behind FEATURE_EDGE_PROXY)
     */
    public function rotateEdgeProxyToken(Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }
        if (!\App\Support\Feature::enabled('edge_proxy')) {
            abort(404);
        }

        $connector = DomainMetaConnector::where('domain_id', $domain->id)->where('type', 'edge_proxy')->first();
        if (!$connector) {
            return back()->with('error', 'Edge connector not configured');
        }

        $authJson = $connector->auth_json ?? [];
        $authJson['edge_token'] = Str::random(64);
        $authJson['cache_ttl'] = (int) ($authJson['cache_ttl'] ?? 300);
        $connector->update(['auth_json' => $authJson]);

        return back()->with('success', 'Edge token rotated. Update your Worker script with the new token.');
    }
}

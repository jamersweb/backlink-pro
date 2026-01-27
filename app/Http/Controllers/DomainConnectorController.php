<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainConnector;
use App\Services\Meta\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DomainConnectorController extends Controller
{
    /**
     * Show connector setup page
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $connector = $domain->connector;

        return Inertia::render('Domains/Meta/Connector', [
            'domain' => $domain,
            'connector' => $connector,
        ]);
    }

    /**
     * Save/update connector configuration
     */
    public function store(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'type' => 'required|in:wp,shopify,generic,custom_js',
            'credentials' => 'required|array',
            'settings' => 'nullable|array',
        ]);

        $type = $validated['type'];
        $credentials = $validated['credentials'];
        $settings = $validated['settings'] ?? [];

        // Validate credentials based on type
        $this->validateCredentials($type, $credentials, $settings);

        // Create or update connector
        $connector = DomainConnector::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'type' => $type,
                'status' => DomainConnector::STATUS_DISCONNECTED,
                'credentials_json' => $credentials,
                'settings_json' => $settings,
            ]
        );

        return back()->with('success', 'Connector configuration saved');
    }

    /**
     * Test connector connection
     */
    public function test(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $connector = $domain->connector;

        if (!$connector) {
            return back()->with('error', 'No connector configured');
        }

        try {
            $connectorService = ConnectorFactory::make($connector->type);
            $result = $connectorService->testConnection($connector);

            // Update connector status and test timestamp
            $connector->update([
                'status' => $result['ok'] 
                    ? DomainConnector::STATUS_CONNECTED 
                    : DomainConnector::STATUS_ERROR,
                'last_tested_at' => now(),
                'last_error_code' => $result['error_code'] ?? null,
                'last_error_message' => $result['message'] ?? null,
            ]);

            if ($result['ok']) {
                return back()->with('success', 'Connection test successful');
            } else {
                return back()->with('error', $result['message'] ?? 'Connection test failed');
            }
        } catch (\Exception $e) {
            $connector->update([
                'status' => DomainConnector::STATUS_ERROR,
                'last_tested_at' => now(),
                'last_error_code' => 'UNKNOWN',
                'last_error_message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete/disconnect connector
     */
    public function destroy(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $connector = $domain->connector;

        if ($connector) {
            $connector->delete();
        }

        return back()->with('success', 'Connector disconnected');
    }

    /**
     * Validate credentials based on connector type
     */
    protected function validateCredentials(string $type, array $credentials, array $settings): void
    {
        switch ($type) {
            case DomainConnector::TYPE_WP:
                if (empty($settings['wp_base_url']) || empty($credentials['username']) || empty($credentials['app_password'])) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['credentials' => 'WordPress base URL, username, and application password are required']
                    );
                }
                break;

            case DomainConnector::TYPE_SHOPIFY:
                if (empty($settings['shop']) || empty($credentials['access_token'])) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['credentials' => 'Shop domain and access token are required']
                    );
                }
                break;

            case DomainConnector::TYPE_GENERIC:
                if (empty($settings['base_url'])) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], []),
                        ['settings' => 'Base URL is required for generic connector']
                    );
                }
                break;

            case DomainConnector::TYPE_CUSTOM_JS:
                // No credentials needed for custom JS
                break;
        }
    }
}

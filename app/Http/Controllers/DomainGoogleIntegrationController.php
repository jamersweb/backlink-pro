<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainGoogleIntegration;
use App\Services\Google\SearchConsoleService;
use App\Services\Google\Ga4Service;
use App\Jobs\Integrations\SyncGscDomainJob;
use App\Jobs\Integrations\SyncGa4DomainJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DomainGoogleIntegrationController extends Controller
{
    /**
     * Show integrations page
     */
    public function index(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $integration = $domain->googleIntegration;
        $gscSites = [];
        $ga4Properties = [];
        $gscDaily = [];
        $ga4Daily = [];
        $topPages = [];
        $topQueries = [];

        if ($integration && $integration->connectedAccount && $integration->connectedAccount->isActive()) {
            try {
                // Load available properties
                $gscService = new SearchConsoleService($integration->connectedAccount);
                $gscSites = $gscService->listSites();

                $ga4Service = new Ga4Service($integration->connectedAccount);
                $ga4Properties = $ga4Service->listProperties();

                // Load metrics for last 28 days
                $startDate = now()->subDays(28);
                $endDate = now();

                if ($integration->gsc_property) {
                    $gscDaily = $domain->gscDailyMetrics()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->orderBy('date')
                        ->get();

                    // Get top pages and queries (latest snapshot)
                    $latestDate = \App\Models\GscTopPage::where('domain_id', $domain->id)
                        ->max('date');
                    if ($latestDate) {
                        $topPages = \App\Models\GscTopPage::where('domain_id', $domain->id)
                            ->where('date', $latestDate)
                            ->orderBy('clicks', 'desc')
                            ->limit(10)
                            ->get();

                        $topQueries = \App\Models\GscTopQuery::where('domain_id', $domain->id)
                            ->where('date', $latestDate)
                            ->orderBy('clicks', 'desc')
                            ->limit(10)
                            ->get();
                    }
                }

                if ($integration->ga4_property_id) {
                    $ga4Daily = $domain->ga4DailyMetrics()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->orderBy('date')
                        ->get();
                }
            } catch (\Exception $e) {
                \Log::error('Failed to load integration data', [
                    'domain_id' => $domain->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('Domains/Integrations/Google', [
            'domain' => $domain,
            'integration' => $integration ? [
                'id' => $integration->id,
                'status' => $integration->status,
                'gsc_property' => $integration->gsc_property,
                'ga4_property_id' => $integration->ga4_property_id,
                'last_synced_at' => $integration->last_synced_at,
                'account_email' => $integration->connectedAccount->email ?? null,
            ] : null,
            'selectable' => [
                'gscSites' => $gscSites,
                'ga4Properties' => $ga4Properties,
            ],
            'metrics' => [
                'gscDaily' => $gscDaily,
                'ga4Daily' => $ga4Daily,
            ],
            'top' => [
                'pages' => $topPages,
                'queries' => $topQueries,
            ],
        ]);
    }

    /**
     * Save property selections
     */
    public function saveSelection(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'gsc_property' => 'nullable|string',
            'ga4_property_id' => 'nullable|string',
        ]);

        $integration = $domain->googleIntegration;

        if (!$integration) {
            return back()->withErrors([
                'integration' => 'Please connect a Google account first.',
            ]);
        }

        $integration->update([
            'gsc_property' => $validated['gsc_property'] ?? null,
            'ga4_property_id' => $validated['ga4_property_id'] ?? null,
        ]);

        return back()->with('success', 'Properties saved successfully!');
    }

    /**
     * Sync now
     */
    public function syncNow(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Check quota limits (only for user-triggered sync)
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'google.sync_now_per_day', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
            ]);
        }

        $integration = $domain->googleIntegration;

        if (!$integration || $integration->status !== DomainGoogleIntegration::STATUS_CONNECTED) {
            return back()->withErrors([
                'integration' => 'Integration is not connected.',
            ]);
        }

        // Consume quota
        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'google.sync_now_per_day', 1, 'day', [
            'domain_id' => $domain->id,
        ]);

        // Dispatch sync jobs
        if ($integration->gsc_property) {
            SyncGscDomainJob::dispatch($domain->id);
        }

        if ($integration->ga4_property_id) {
            SyncGa4DomainJob::dispatch($domain->id);
        }

        return back()->with('success', 'Sync started. Data will be available shortly.');
    }

    /**
     * Disconnect integration
     */
    public function disconnect(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $integration = $domain->googleIntegration;

        if ($integration) {
            $integration->update([
                'status' => DomainGoogleIntegration::STATUS_DISCONNECTED,
                'gsc_property' => null,
                'ga4_property_id' => null,
            ]);
        }

        return back()->with('success', 'Integration disconnected successfully.');
    }
}

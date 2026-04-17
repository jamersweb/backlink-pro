<?php

namespace App\Http\Controllers;

use App\Jobs\Integrations\SyncAuditGscJob;
use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\SearchConsoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditGscController extends Controller
{
    public function properties(int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'connected' => false,
                'sites' => [],
                'selected_site_url' => null,
                'message' => 'Search Console account is not connected.',
            ]);
        }

        $gsc = new SearchConsoleService($account);
        $sites = $gsc->listSites();

        $selected = data_get($audit->audit_kpis, 'gsc.selected_site_url')
            ?: data_get($audit->audit_kpis, 'gsc.site_url')
            ?: $this->deriveDefaultSiteUrl($sites, $audit->normalized_url);

        return response()->json([
            'connected' => true,
            'sites' => $sites,
            'selected_site_url' => $selected,
        ]);
    }

    public function selectProperty(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Search Console account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'site_url' => ['required', 'string', 'max:512'],
        ]);

        $gsc = new SearchConsoleService($account);
        $sites = collect($gsc->listSites());
        $selectedSite = $validated['site_url'];

        if (! $sites->contains(fn ($site) => ($site['siteUrl'] ?? null) === $selectedSite)) {
            return response()->json([
                'error' => 'Selected property is not available in your Search Console account.',
            ], 422);
        }

        $kpis = $audit->audit_kpis ?? [];
        $kpis['gsc'] = $kpis['gsc'] ?? [];
        $kpis['gsc']['connected'] = true;
        $kpis['gsc']['selected_site_url'] = $selectedSite;
        $kpis['gsc']['site_url'] = $selectedSite;
        $audit->audit_kpis = $kpis;
        $audit->save();

        return response()->json([
            'selected_site_url' => $selectedSite,
            'message' => 'Search Console property selected.',
        ]);
    }

    public function sync(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Search Console account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'site_url' => ['nullable', 'string', 'max:512'],
        ]);

        $gsc = new SearchConsoleService($account);
        $sites = $gsc->listSites();

        $siteUrl = $validated['site_url']
            ?? data_get($audit->audit_kpis, 'gsc.selected_site_url')
            ?? data_get($audit->audit_kpis, 'gsc.site_url')
            ?? $this->deriveDefaultSiteUrl($sites, $audit->normalized_url);

        if (! $siteUrl) {
            return response()->json([
                'error' => 'No matching Search Console property found for this audit.',
            ], 422);
        }

        $isKnownSite = collect($sites)->contains(fn ($site) => ($site['siteUrl'] ?? null) === $siteUrl);
        if (! $isKnownSite) {
            return response()->json([
                'error' => 'Selected Search Console property is no longer available.',
            ], 422);
        }

        $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
        $gsc = $kpis['gsc'] ?? [];
        $gsc['sync_status'] = 'queued';
        unset($gsc['sync_error']);
        $kpis['gsc'] = $gsc;
        $audit->audit_kpis = $kpis;
        $audit->save();

        SyncAuditGscJob::dispatch($audit->id, (int) Auth::id(), $validated['site_url'] ?? null);

        return response()->json([
            'queued' => true,
            'gsc' => $audit->fresh()->audit_kpis['gsc'] ?? $gsc,
        ]);
    }

    protected function deriveDefaultSiteUrl(array $sites, string $normalizedUrl): ?string
    {
        $auditHost = parse_url($normalizedUrl, PHP_URL_HOST) ?: '';
        foreach ($sites as $site) {
            $siteUrl = $site['siteUrl'] ?? null;
            if (! $siteUrl) {
                continue;
            }
            $siteHost = parse_url($siteUrl, PHP_URL_HOST) ?: str_replace('sc-domain:', '', $siteUrl);
            if ($auditHost !== '' && ($siteHost === $auditHost || str_contains($siteUrl, $auditHost))) {
                return $siteUrl;
            }
        }

        return $sites[0]['siteUrl'] ?? null;
    }

    protected function activeGoogleSeoAccount(): ?ConnectedAccount
    {
        return ConnectedAccount::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();
    }

    protected function loadOwnedAudit(int $id): Audit
    {
        return Audit::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}


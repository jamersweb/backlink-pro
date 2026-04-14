<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\Ga4Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditGa4Controller extends Controller
{
    public function properties(int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'connected' => false,
                'properties' => [],
                'selected_property_id' => null,
                'message' => 'Google Analytics account is not connected.',
            ]);
        }

        $ga4 = new Ga4Service($account);
        $properties = $ga4->listProperties();

        $selected = data_get($audit->audit_kpis, 'ga4.selected_property_id')
            ?: data_get($audit->audit_kpis, 'ga4.property_id')
            ?: ($properties[0]['propertyName'] ?? null);

        return response()->json([
            'connected' => true,
            'properties' => $properties,
            'selected_property_id' => $selected,
        ]);
    }

    public function selectProperty(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Google Analytics account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'property_id' => ['required', 'string', 'max:128'],
        ]);

        $propertyId = $this->normalizePropertyId($validated['property_id']);
        $ga4 = new Ga4Service($account);
        $properties = collect($ga4->listProperties());

        $selectedProperty = $properties->first(fn ($p) => ($p['propertyName'] ?? null) === $propertyId);
        if (! $selectedProperty) {
            return response()->json([
                'error' => 'Selected GA4 property is not available in your account.',
            ], 422);
        }

        $kpis = $audit->audit_kpis ?? [];
        $kpis['ga4'] = $kpis['ga4'] ?? [];
        $kpis['ga4']['connected'] = true;
        $kpis['ga4']['selected_property_id'] = $propertyId;
        $kpis['ga4']['property_id'] = $propertyId;
        $kpis['ga4']['property'] = $selectedProperty['displayName'] ?? $propertyId;
        $audit->audit_kpis = $kpis;
        $audit->save();

        return response()->json([
            'selected_property_id' => $propertyId,
            'property_name' => $selectedProperty['displayName'] ?? $propertyId,
        ]);
    }

    public function sync(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Google Analytics account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'property_id' => ['nullable', 'string', 'max:128'],
        ]);

        $ga4 = new Ga4Service($account);
        $properties = $ga4->listProperties();

        $propertyId = $validated['property_id']
            ?: data_get($audit->audit_kpis, 'ga4.selected_property_id')
            ?: data_get($audit->audit_kpis, 'ga4.property_id')
            ?: ($properties[0]['propertyName'] ?? null);
        $propertyId = $propertyId ? $this->normalizePropertyId($propertyId) : null;

        if (! $propertyId) {
            return response()->json([
                'error' => 'No GA4 property found for this account.',
            ], 422);
        }

        $selectedProperty = collect($properties)->first(fn ($p) => ($p['propertyName'] ?? null) === $propertyId);
        if (! $selectedProperty) {
            return response()->json([
                'error' => 'Selected GA4 property is no longer available.',
            ], 422);
        }

        $endDate = new \DateTime('now');
        $startDate = (clone $endDate)->modify('-' . $this->resolveReportPeriodDays($audit) . ' days');

        try {
            $dailyMetrics = $ga4->runDailyReport($propertyId, $startDate, $endDate);
            $landingPages = $ga4->runLandingPagesReport($propertyId, $startDate, $endDate, 20);
            $topSources = $ga4->runTopSourcesReport($propertyId, $startDate, $endDate, 20);

            $totalSessions = array_sum(array_column($dailyMetrics, 'sessions'));
            $totalUsers = array_sum(array_column($dailyMetrics, 'total_users'));
            $avgEngagementRate = ! empty($dailyMetrics)
                ? round(array_sum(array_column($dailyMetrics, 'engagement_rate')) / count($dailyMetrics) * 100, 1)
                : 0;

            $ga4Payload = [
                'connected' => true,
                'property' => $selectedProperty['displayName'] ?? $propertyId,
                'property_id' => $propertyId,
                'selected_property_id' => $propertyId,
                'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'summary' => [
                    'total_sessions' => $totalSessions,
                    'total_users' => $totalUsers,
                    'avg_engagement_rate' => $avgEngagementRate,
                ],
                'daily' => $dailyMetrics,
                'top_pages' => $landingPages,
                'top_sources' => $topSources,
            ];

            $kpis = $audit->audit_kpis ?? [];
            $kpis['ga4'] = $ga4Payload;
            $audit->audit_kpis = $kpis;
            $audit->ga4_ready_at = now();
            $audit->save();

            return response()->json([
                'ga4' => $ga4Payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit GA4 sync failed', [
                'audit_id' => $audit->id,
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'GA4 sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function normalizePropertyId(string $propertyId): string
    {
        $propertyId = trim($propertyId);
        if ($propertyId === '') {
            return $propertyId;
        }

        return str_starts_with($propertyId, 'properties/')
            ? $propertyId
            : 'properties/' . $propertyId;
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

    protected function resolveReportPeriodDays(Audit $audit): int
    {
        $audit->loadMissing('organization.brandingProfile');
        $days = (int) ($audit->organization?->brandingProfile?->report_period_days ?: 30);

        return in_array($days, [7, 15, 30], true) ? $days : 30;
    }
}


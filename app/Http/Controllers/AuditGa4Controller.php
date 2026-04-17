<?php

namespace App\Http\Controllers;

use App\Jobs\Integrations\SyncAuditGa4Job;
use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\Ga4Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
        $ga = $kpis['ga4'] ?? [];
        $ga['sync_status'] = 'queued';
        unset($ga['sync_error']);
        $kpis['ga4'] = $ga;
        $audit->audit_kpis = $kpis;
        $audit->save();

        SyncAuditGa4Job::dispatch($audit->id, (int) Auth::id(), $validated['property_id'] ?? null);

        return response()->json([
            'queued' => true,
            'ga4' => $audit->fresh()->audit_kpis['ga4'] ?? $ga,
        ]);
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
}


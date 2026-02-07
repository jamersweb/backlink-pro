<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\BrandingProfile;
use App\Models\CustomDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class OrgSettingsController extends Controller
{
    /**
     * Show branding settings
     */
    public function branding(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $branding = $organization->brandingProfile ?? BrandingProfile::create([
            'organization_id' => $organization->id,
        ]);

        return Inertia::render('Organizations/Settings/Branding', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'branding' => [
                'brand_name' => $branding->brand_name,
                'logo_path' => $branding->logo_path,
                'primary_color' => $branding->primary_color,
                'secondary_color' => $branding->secondary_color,
                'accent_color' => $branding->accent_color,
                'report_footer_text' => $branding->report_footer_text,
                'hide_backlinkpro_branding' => $branding->hide_backlinkpro_branding,
                'pdf_template' => $branding->pdf_template,
                'email_from_name' => $branding->email_from_name,
                'email_from_address' => $branding->email_from_address,
            ],
        ]);
    }

    /**
     * Update branding settings
     */
    public function updateBranding(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'brand_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'report_footer_text' => ['nullable', 'string', 'max:500'],
            'hide_backlinkpro_branding' => ['boolean'],
            'pdf_template' => ['in:default,pro'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
        ]);

        $branding = $organization->brandingProfile ?? BrandingProfile::create([
            'organization_id' => $organization->id,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }

            $path = $request->file('logo')->store('branding/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $branding->update($validated);

        return back()->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Show custom domains settings
     */
    public function domains(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $domains = $organization->customDomains()->get();

        return Inertia::render('Organizations/Settings/Domains', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'domains' => $domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'status' => $domain->status,
                    'verification_token' => $domain->verification_token,
                    'last_checked_at' => $domain->last_checked_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Add custom domain
     */
    public function addDomain(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        // Check plan feature
        $planLimiter = new \App\Services\Billing\PlanLimiter();
        if (!$planLimiter->canUseCustomDomain($organization)) {
            return back()->withErrors(['domain' => 'Custom domains are not available on your plan.']);
        }

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:custom_domains,domain'],
        ]);

        $domain = CustomDomain::create([
            'organization_id' => $organization->id,
            'domain' => $validated['domain'],
            'status' => CustomDomain::STATUS_PENDING,
            'verification_token' => Str::random(32),
        ]);

        return back()->with('success', 'Domain added. Please verify DNS configuration.');
    }

    /**
     * Remove custom domain
     */
    public function removeDomain(Organization $organization, CustomDomain $domain)
    {
        $this->authorize('manage', $organization);

        if ($domain->organization_id !== $organization->id) {
            abort(403);
        }

        $domain->delete();

        return back()->with('success', 'Domain removed.');
    }

    /**
     * Show PageSpeed settings
     */
    public function pagespeed(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $masked = null;
        if ($organization->pagespeed_api_key_encrypted) {
            $key = $organization->pagespeed_api_key_encrypted;
            $masked = '****' . substr($key, -4);
        }

        $status = 'Not set';
        if ($organization->pagespeed_last_key_verified_at) {
            $status = 'Valid';
        } elseif ($organization->pagespeed_byok_enabled && $organization->pagespeed_api_key_encrypted) {
            $status = 'Not verified';
        }

        return Inertia::render('Organizations/Settings/PageSpeed', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'settings' => [
                'pagespeed_byok_enabled' => (bool) $organization->pagespeed_byok_enabled,
                'masked_key' => $masked,
                'status_label' => $status,
            ],
        ]);
    }

    /**
     * Update PageSpeed settings
     */
    public function updatePagespeed(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'pagespeed_byok_enabled' => ['boolean'],
            'pagespeed_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($validated['pagespeed_api_key'])) {
            $organization->pagespeed_api_key_encrypted = $validated['pagespeed_api_key'];
            $organization->pagespeed_last_key_verified_at = null;
        }

        $organization->pagespeed_byok_enabled = (bool) ($validated['pagespeed_byok_enabled'] ?? false);
        $organization->save();

        return back()->with('success', 'PageSpeed settings updated.');
    }

    /**
     * Verify PageSpeed API key
     */
    public function verifyPagespeed(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'pagespeed_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $apiKey = $validated['pagespeed_api_key'] ?? $organization->pagespeed_api_key_encrypted;
        if (!$apiKey) {
            return back()->withErrors(['pagespeed_api_key' => 'API key is required to verify.']);
        }

        $response = Http::timeout(20)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
            'url' => 'https://example.com',
            'strategy' => 'mobile',
            'category' => ['performance'],
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            return back()->withErrors(['pagespeed_api_key' => 'Invalid API key.']);
        }

        $organization->pagespeed_api_key_encrypted = $apiKey;
        $organization->pagespeed_byok_enabled = true;
        $organization->pagespeed_last_key_verified_at = now();
        $organization->save();

        return back()->with('success', 'PageSpeed API key verified.');
    }
}

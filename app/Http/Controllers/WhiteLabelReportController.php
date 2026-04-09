<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWhiteLabelSettingsRequest;
use App\Models\BrandingProfile;
use App\Models\Organization;
use App\Services\Billing\PlanLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WhiteLabelReportController extends Controller
{
    private const DEFAULTS = [
        'enabled' => false,
        'company_name' => '',
        'logo_url' => null,
        'logo_path' => null,
        'primary_color' => '#ff8a65',
        'secondary_color' => '#ffcfb9',
        'website' => '',
        'support_email' => '',
        'footer_text' => '',
        'use_custom_cover_title' => false,
        'custom_cover_title' => '',
    ];

    public function index(Request $request): Response
    {
        $organization = $this->resolveOrganization($request);
        if ($organization) {
            $this->authorize('manage', $organization);
        }

        $branding = $organization?->brandingProfile;
        $planLimiter = app(PlanLimiter::class);
        $canUseWhiteLabel = $organization ? $planLimiter->canUseWhiteLabel($organization) : false;

        return Inertia::render('WhiteLabelReport/Index', [
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'plan_key' => $organization->plan_key,
                'plan_status' => $organization->plan_status,
            ] : null,
            'canUseWhiteLabel' => $canUseWhiteLabel,
            'upgradeUrl' => $organization ? route('orgs.billing.plans', $organization) : '/plans',
            'settings' => $this->formatSettings($branding),
            'defaultSettings' => self::DEFAULTS,
            'reportHighlights' => [
                [
                    'title' => 'Own your brand experience',
                    'description' => 'Replace platform branding with your logo, company name and color palette for every client-facing report.',
                    'icon' => 'bi-palette',
                ],
                [
                    'title' => 'Send polished deliverables',
                    'description' => 'Present backlink progress, SEO wins and recommendations in a clean format your clients can forward with confidence.',
                    'icon' => 'bi-file-earmark-text',
                ],
                [
                    'title' => 'Keep delivery consistent',
                    'description' => 'Use one branded reporting workflow across campaigns so every account feels organized and premium.',
                    'icon' => 'bi-stars',
                ],
            ],
            'setupSteps' => [
                'Enable white label mode for this workspace',
                'Upload a logo, colors and support contact details',
                'Preview the report header and footer before saving',
            ],
        ]);
    }

    public function update(UpdateWhiteLabelSettingsRequest $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);

        if (!$organization) {
            return back()->withErrors(['organization' => 'Create or join a workspace before saving white label settings.']);
        }

        $this->authorize('manage', $organization);

        $planLimiter = app(PlanLimiter::class);
        if (!$planLimiter->canUseWhiteLabel($organization)) {
            return back()->withErrors(['plan' => 'White label branding is available on the Agency plan.']);
        }

        $branding = $organization->brandingProfile ?? BrandingProfile::create([
            'organization_id' => $organization->id,
        ]);

        $validated = $request->validated();
        $data = [
            'white_label_enabled' => (bool) $validated['enabled'],
            'brand_name' => $validated['company_name'] ?: null,
            'primary_color' => $validated['primary_color'] ?: null,
            'secondary_color' => $validated['secondary_color'] ?: null,
            'accent_color' => $validated['secondary_color'] ?: null,
            'website' => $validated['website'] ?: null,
            'support_email' => $validated['support_email'] ?: null,
            'report_footer_text' => $validated['footer_text'] ?: null,
            'use_custom_cover_title' => (bool) $validated['use_custom_cover_title'],
            'custom_cover_title' => ($validated['use_custom_cover_title'] ?? false) ? ($validated['custom_cover_title'] ?: null) : null,
        ];

        $removeLogo = (bool) ($validated['remove_logo'] ?? false);
        if ($removeLogo && $branding->logo_path) {
            Storage::disk('public')->delete($branding->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('branding/logos', 'public');
        }

        $branding->update($data);

        return back()->with('success', 'White label branding settings updated successfully.');
    }

    private function resolveOrganization(Request $request): ?Organization
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $scopedOrganization = $request->attributes->get('currentOrganization');
        if ($scopedOrganization instanceof Organization && $scopedOrganization->hasUser($user)) {
            return $scopedOrganization;
        }

        $ownedOrganization = Organization::query()
            ->where('owner_user_id', $user->id)
            ->orderBy('id')
            ->first();

        if ($ownedOrganization) {
            return $ownedOrganization;
        }

        return Organization::query()
            ->whereHas('users', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'admin']))
            ->orderBy('id')
            ->first();
    }

    private function formatSettings(?BrandingProfile $branding): array
    {
        if (!$branding) {
            return self::DEFAULTS;
        }

        return [
            'enabled' => (bool) $branding->white_label_enabled,
            'company_name' => $branding->brand_name ?? '',
            'logo_url' => $branding->logo_path ? Storage::disk('public')->url($branding->logo_path) : null,
            'logo_path' => $branding->logo_path,
            'primary_color' => $branding->primary_color ?: self::DEFAULTS['primary_color'],
            'secondary_color' => $branding->secondary_color ?: self::DEFAULTS['secondary_color'],
            'website' => $branding->website ?? '',
            'support_email' => $branding->support_email ?? '',
            'footer_text' => $branding->report_footer_text ?? '',
            'use_custom_cover_title' => (bool) $branding->use_custom_cover_title,
            'custom_cover_title' => $branding->custom_cover_title ?? '',
        ];
    }
}

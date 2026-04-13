<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWhiteLabelSettingsRequest;
use App\Http\Requests\UpsertWhiteLabelReportProfileRequest;
use App\Models\BrandingProfile;
use App\Models\Domain;
use App\Models\Organization;
use App\Models\WhiteLabelReportProfile;
use App\Services\Reports\WhiteLabelReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        'primary_color' => '#FF5626',
        'secondary_color' => '#1C1B1B',
        'website' => '',
        'support_email' => '',
        'support_phone' => '',
        'company_address' => '',
        'footer_text' => '',
        'report_period_days' => 30,
        'report_sections' => [
            'on_page' => [
                'title_optimization' => true,
                'meta_descriptions' => true,
                'heading_structure' => true,
                'content_quality' => false,
                'internal_linking' => false,
            ],
            'off_page' => [
                'backlink_quality' => true,
                'referring_domains' => true,
                'anchor_text_profile' => false,
                'link_velocity' => false,
            ],
            'technical_seo' => [
                'crawlability' => true,
                'indexability' => true,
                'pagespeed' => true,
                'structured_data' => false,
                'mobile_usability' => false,
            ],
        ],
        'use_custom_cover_title' => false,
        'custom_cover_title' => '',
    ];

    public function index(Request $request): Response
    {
        return $this->renderPage($request, 'branding');
    }

    public function reports(Request $request): Response
    {
        return $this->renderPage($request, 'reports');
    }

    public function preview(Request $request, WhiteLabelReportProfile $profile, WhiteLabelReportBuilder $builder): Response
    {
        return $this->renderPage($request, 'preview', $this->resolveOwnedProfile($request, $profile), $builder);
    }

    public function legacyIndex(): RedirectResponse
    {
        return redirect()->route('label.index');
    }

    public function update(UpdateWhiteLabelSettingsRequest $request): RedirectResponse
    {
        $organization = $this->requireOrganization($request);
        Gate::authorize('view', $organization);

        $branding = $organization->brandingProfile ?? BrandingProfile::create([
            'organization_id' => $organization->id,
        ]);

        $validated = $request->validated();
        $data = [
            'white_label_enabled' => (bool) $validated['enabled'],
            'brand_name' => $validated['company_name'] ?: null,
            'website' => $validated['website'] ?: null,
            'primary_color' => $validated['primary_color'] ?: null,
            'secondary_color' => $validated['secondary_color'] ?: null,
            'support_email' => $validated['support_email'] ?: null,
            'support_phone' => $validated['support_phone'] ?: null,
            'company_address' => $validated['company_address'] ?: null,
            'report_footer_text' => $validated['footer_text'] ?: null,
            'report_period_days' => (int) ($validated['report_period_days'] ?? self::DEFAULTS['report_period_days']),
            'report_sections_json' => $validated['report_sections'] ?? self::DEFAULTS['report_sections'],
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

        return redirect()->route('label.index')->with('success', 'Branding settings updated successfully.');
    }

    public function storeProfile(UpsertWhiteLabelReportProfileRequest $request): RedirectResponse
    {
        $organization = $this->requireOrganization($request);
        Gate::authorize('view', $organization);
        $this->ensureProfilesTableExists();

        $validated = $request->validated();
        $domain = $this->resolveOwnedDomain($validated['domain_id'] ?? null);

        $profile = WhiteLabelReportProfile::create([
            'organization_id' => $organization->id,
            'user_id' => Auth::id(),
            'domain_id' => $domain?->id,
            'client_name' => $validated['client_name'],
            'client_website' => $validated['client_website'],
            'report_title' => $validated['report_title'],
            'reporting_period_start' => $validated['reporting_period_start'],
            'reporting_period_end' => $validated['reporting_period_end'],
            'target_keywords' => $validated['target_keywords'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
        ]);

        return redirect()->route('label.preview', $profile)->with('success', 'Client report profile created successfully.');
    }

    public function updateProfile(UpsertWhiteLabelReportProfileRequest $request, WhiteLabelReportProfile $profile): RedirectResponse
    {
        $this->ensureProfilesTableExists();
        $profile = $this->resolveOwnedProfile($request, $profile);
        $validated = $request->validated();
        $domain = $this->resolveOwnedDomain($validated['domain_id'] ?? null);

        $profile->update([
            'domain_id' => $domain?->id,
            'client_name' => $validated['client_name'],
            'client_website' => $validated['client_website'],
            'report_title' => $validated['report_title'],
            'reporting_period_start' => $validated['reporting_period_start'],
            'reporting_period_end' => $validated['reporting_period_end'],
            'target_keywords' => $validated['target_keywords'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
        ]);

        return redirect()->route('label.preview', $profile)->with('success', 'Client report profile updated successfully.');
    }

    public function destroyProfile(Request $request, WhiteLabelReportProfile $profile): RedirectResponse
    {
        $this->ensureProfilesTableExists();
        $profile = $this->resolveOwnedProfile($request, $profile);
        $profile->delete();

        return redirect()->route('label.reports')->with('success', 'Client report profile deleted successfully.');
    }

    public function pdf(Request $request, WhiteLabelReportProfile $profile, WhiteLabelReportBuilder $builder)
    {
        $this->ensureProfilesTableExists();
        $profile = $this->resolveOwnedProfile($request, $profile);
        $organization = $this->requireOrganization($request);
        $report = $builder->build($organization, $profile);

        $html = view('label.report-pdf', [
            'report' => $report,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('A4');
        if (method_exists($pdf, 'setOption')) {
            $pdf->setOption('isRemoteEnabled', true);
        }

        $filename = 'white-label-seo-report-' . $profile->id . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function renderPage(
        Request $request,
        string $activeTab,
        ?WhiteLabelReportProfile $selectedProfile = null,
        ?WhiteLabelReportBuilder $builder = null
    ): Response {
        $profilesTableExists = Schema::hasTable('white_label_report_profiles');
        $organization = $this->resolveOrganization($request);
        if ($organization) {
            Gate::authorize('view', $organization);
        }

        $profiles = collect();
        $domains = collect();
        $previewReport = null;

        if ($organization && $profilesTableExists) {
            $profiles = WhiteLabelReportProfile::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', Auth::id())
                ->latest()
                ->get()
                ->map(fn (WhiteLabelReportProfile $profile) => [
                    'id' => $profile->id,
                    'domain_id' => $profile->domain_id,
                    'client_name' => $profile->client_name,
                    'client_website' => $profile->client_website,
                    'report_title' => $profile->report_title,
                    'reporting_period_start' => optional($profile->reporting_period_start)->format('Y-m-d'),
                    'reporting_period_end' => optional($profile->reporting_period_end)->format('Y-m-d'),
                    'target_keywords' => $profile->target_keywords,
                    'notes' => $profile->notes,
                    'recommendations' => $profile->recommendations,
                    'preview_url' => route('label.preview', $profile),
                    'pdf_url' => route('label.pdf', $profile),
                ])
                ->values();

            $domains = Domain::query()
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'host', 'url'])
                ->map(fn (Domain $domain) => [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'host' => $domain->host,
                    'url' => $domain->url,
                ])
                ->values();
        }

        if ($selectedProfile && $organization && $profilesTableExists) {
            $previewReport = ($builder ?? app(WhiteLabelReportBuilder::class))->build($organization, $selectedProfile);
        }

        return Inertia::render('WhiteLabelReport/Index', [
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'plan_key' => $organization->plan_key,
                'plan_status' => $organization->plan_status,
            ] : null,
            'canUseWhiteLabel' => (bool) $organization,
            'activeTab' => $activeTab,
            'branding' => $this->formatSettings($organization?->brandingProfile),
            'defaultSettings' => self::DEFAULTS,
            'profiles' => $profiles,
            'domains' => $domains,
            'selectedProfile' => $selectedProfile ? [
                'id' => $selectedProfile->id,
                'domain_id' => $selectedProfile->domain_id,
                'client_name' => $selectedProfile->client_name,
                'client_website' => $selectedProfile->client_website,
                'report_title' => $selectedProfile->report_title,
                'reporting_period_start' => optional($selectedProfile->reporting_period_start)->format('Y-m-d'),
                'reporting_period_end' => optional($selectedProfile->reporting_period_end)->format('Y-m-d'),
                'target_keywords' => $selectedProfile->target_keywords,
                'notes' => $selectedProfile->notes,
                'recommendations' => $selectedProfile->recommendations,
                'pdf_url' => route('label.pdf', $selectedProfile),
            ] : null,
            'previewReport' => $previewReport,
            'profilesTableExists' => $profilesTableExists,
            'setupWarning' => $profilesTableExists
                ? null
                : 'White label report profiles table is missing. Run the latest migrations to enable client report creation and previews.',
            'tabLinks' => [
                'branding' => route('label.index'),
                'reports' => route('label.reports'),
                'preview' => $selectedProfile ? route('label.preview', $selectedProfile) : null,
            ],
        ]);
    }

    private function requireOrganization(Request $request): Organization
    {
        $organization = $this->resolveOrganization($request);

        abort_if(!$organization, 403, 'Create or join a workspace before using the Label section.');

        return $organization;
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
            ->whereHas('users', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('id')
            ->first();
    }

    private function resolveOwnedProfile(Request $request, WhiteLabelReportProfile $profile): WhiteLabelReportProfile
    {
        $organization = $this->requireOrganization($request);

        abort_unless(
            $profile->organization_id === $organization->id && $profile->user_id === Auth::id(),
            403
        );

        return $profile;
    }

    private function resolveOwnedDomain(?int $domainId): ?Domain
    {
        if (!$domainId) {
            return null;
        }

        return Domain::query()
            ->where('id', $domainId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    private function ensureProfilesTableExists(): void
    {
        abort_unless(
            Schema::hasTable('white_label_report_profiles'),
            500,
            'The white_label_report_profiles table is missing. Please run the latest migrations.'
        );
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
            'primary_color' => $branding->primary_color ?? self::DEFAULTS['primary_color'],
            'secondary_color' => $branding->secondary_color ?? self::DEFAULTS['secondary_color'],
            'website' => $branding->website ?? '',
            'support_email' => $branding->support_email ?? '',
            'support_phone' => $branding->support_phone ?? '',
            'company_address' => $branding->company_address ?? '',
            'footer_text' => $branding->report_footer_text ?? '',
            'report_period_days' => (int) ($branding->report_period_days ?: self::DEFAULTS['report_period_days']),
            'report_sections' => $branding->report_sections_json ?: self::DEFAULTS['report_sections'],
            'use_custom_cover_title' => (bool) $branding->use_custom_cover_title,
            'custom_cover_title' => $branding->custom_cover_title ?? '',
        ];
    }
}

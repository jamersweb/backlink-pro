<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateWhiteLabelReportRequest;
use App\Http\Requests\UpdateWhiteLabelSettingsRequest;
use App\Http\Requests\UpsertWhiteLabelReportProfileRequest;
use App\Models\BrandingProfile;
use App\Models\Domain;
use App\Models\Organization;
use App\Models\WhiteLabelReport;
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
        'intro_text' => '',
        'outro_text' => '',
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

    public function clients(Request $request): Response
    {
        return $this->renderPage($request, 'clients');
    }

    public function reports(Request $request): Response
    {
        return $this->renderPage($request, 'reports');
    }

    public function preview(Request $request, WhiteLabelReportProfile $profile, WhiteLabelReportBuilder $builder): RedirectResponse
    {
        $organization = $this->requireOrganization($request);
        $profile = $this->resolveOwnedProfile($request, $profile);
        $report = $this->storeGeneratedReport($organization, $profile, $builder);

        return redirect()->route('label.reports.show', $report);
    }

    public function showReport(Request $request, WhiteLabelReport $report): Response
    {
        return $this->renderPage($request, 'reports', $this->resolveOwnedReport($request, $report));
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
            'report_intro_text' => $validated['intro_text'] ?: null,
            'report_outro_text' => $validated['outro_text'] ?: null,
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

    public function storeClient(UpsertWhiteLabelReportProfileRequest $request): RedirectResponse
    {
        $organization = $this->requireOrganization($request);
        Gate::authorize('view', $organization);
        $this->ensureProfilesTableExists();

        $validated = $request->validated();
        $domain = $this->resolveOwnedDomain($validated['domain_id'] ?? null);

        WhiteLabelReportProfile::create([
            'organization_id' => $organization->id,
            'user_id' => Auth::id(),
            'domain_id' => $domain?->id,
            'client_name' => $validated['client_name'],
            'client_website' => $validated['client_website'],
            'client_company_info' => $validated['client_company_info'] ?? null,
            'report_title' => $validated['report_title'],
            'reporting_period_start' => $validated['reporting_period_start'],
            'reporting_period_end' => $validated['reporting_period_end'],
            'target_keywords' => $validated['target_keywords'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
        ]);

        return redirect()->route('label.clients')->with('success', 'Client profile created successfully.');
    }

    public function updateClient(UpsertWhiteLabelReportProfileRequest $request, WhiteLabelReportProfile $profile): RedirectResponse
    {
        $this->ensureProfilesTableExists();
        $profile = $this->resolveOwnedProfile($request, $profile);
        $validated = $request->validated();
        $domain = $this->resolveOwnedDomain($validated['domain_id'] ?? null);

        $profile->update([
            'domain_id' => $domain?->id,
            'client_name' => $validated['client_name'],
            'client_website' => $validated['client_website'],
            'client_company_info' => $validated['client_company_info'] ?? null,
            'report_title' => $validated['report_title'],
            'reporting_period_start' => $validated['reporting_period_start'],
            'reporting_period_end' => $validated['reporting_period_end'],
            'target_keywords' => $validated['target_keywords'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
        ]);

        return redirect()->route('label.clients')->with('success', 'Client profile updated successfully.');
    }

    public function destroyClient(Request $request, WhiteLabelReportProfile $profile): RedirectResponse
    {
        $this->ensureProfilesTableExists();
        $profile = $this->resolveOwnedProfile($request, $profile);
        $profile->delete();

        return redirect()->route('label.clients')->with('success', 'Client profile deleted successfully.');
    }

    public function generateReport(GenerateWhiteLabelReportRequest $request, WhiteLabelReportBuilder $builder): RedirectResponse
    {
        $organization = $this->requireOrganization($request);
        $this->ensureProfilesTableExists();
        $this->ensureReportsTableExists();

        $profile = $this->resolveOwnedProfile(
            $request,
            WhiteLabelReportProfile::query()->findOrFail($request->validated()['profile_id'])
        );

        $report = $this->storeGeneratedReport($organization, $profile, $builder, $request->validated());

        return redirect()->route('label.reports.show', $report)->with('success', 'White-label report generated successfully.');
    }

    public function regenerateReport(Request $request, WhiteLabelReport $report, WhiteLabelReportBuilder $builder): RedirectResponse
    {
        $this->ensureReportsTableExists();
        $report = $this->resolveOwnedReport($request, $report);
        $organization = $this->requireOrganization($request);

        $profile = $report->profile;
        abort_if(!$profile, 404);

        $this->storeGeneratedReport($organization, $profile, $builder, [
            'report_title' => $report->report_title,
            'reporting_period_start' => optional($report->reporting_period_start)->format('Y-m-d'),
            'reporting_period_end' => optional($report->reporting_period_end)->format('Y-m-d'),
        ], $report);

        return redirect()->route('label.reports.show', $report)->with('success', 'Report regenerated successfully.');
    }

    public function destroyReport(Request $request, WhiteLabelReport $report): RedirectResponse
    {
        $this->ensureReportsTableExists();
        $report = $this->resolveOwnedReport($request, $report);
        $report->delete();

        return redirect()->route('label.reports')->with('success', 'Generated report deleted successfully.');
    }

    public function pdf(Request $request, WhiteLabelReport $report)
    {
        $this->ensureReportsTableExists();
        $report = $this->resolveOwnedReport($request, $report);

        $html = view('label.report-pdf', [
            'report' => $report->snapshot_json,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('A4');
        if (method_exists($pdf, 'setOption')) {
            $pdf->setOption('isRemoteEnabled', true);
        }

        $filename = 'white-label-seo-report-' . $report->id . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function renderPage(Request $request, string $activeTab, ?WhiteLabelReport $selectedReport = null): Response
    {
        $profilesTableExists = Schema::hasTable('white_label_report_profiles');
        $reportsTableExists = Schema::hasTable('white_label_reports');
        $organization = $this->resolveOrganization($request);

        if ($organization) {
            Gate::authorize('view', $organization);
        }

        $profiles = collect();
        $domains = collect();
        $reports = collect();

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
                    'client_company_info' => $profile->client_company_info,
                    'report_title' => $profile->report_title,
                    'reporting_period_start' => optional($profile->reporting_period_start)->format('Y-m-d'),
                    'reporting_period_end' => optional($profile->reporting_period_end)->format('Y-m-d'),
                    'target_keywords' => $profile->target_keywords,
                    'notes' => $profile->notes,
                    'recommendations' => $profile->recommendations,
                    'preview_url' => route('label.preview', $profile),
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

        if ($organization && $reportsTableExists) {
            $reports = WhiteLabelReport::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', Auth::id())
                ->latest('generated_at')
                ->latest('id')
                ->get()
                ->map(fn (WhiteLabelReport $report) => [
                    'id' => $report->id,
                    'profile_id' => $report->white_label_report_profile_id,
                    'client_name' => $report->client_name,
                    'client_website' => $report->client_website,
                    'report_title' => $report->report_title,
                    'reporting_period_start' => optional($report->reporting_period_start)->format('Y-m-d'),
                    'reporting_period_end' => optional($report->reporting_period_end)->format('Y-m-d'),
                    'generated_at' => optional($report->generated_at)->toIso8601String(),
                    'status' => $report->status,
                    'preview_url' => route('label.reports.show', $report),
                    'pdf_url' => route('label.reports.pdf', $report),
                ])
                ->values();
        }

        return Inertia::render('WhiteLabelReport/Index', [
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'plan_key' => $organization->plan_key,
                'plan_status' => $organization->plan_status,
            ] : null,
            'activeTab' => $activeTab,
            'branding' => $this->formatSettings($organization?->brandingProfile),
            'defaultSettings' => self::DEFAULTS,
            'profiles' => $profiles,
            'domains' => $domains,
            'reports' => $reports,
            'selectedReport' => $selectedReport ? [
                'id' => $selectedReport->id,
                'profile_id' => $selectedReport->white_label_report_profile_id,
                'client_name' => $selectedReport->client_name,
                'client_website' => $selectedReport->client_website,
                'report_title' => $selectedReport->report_title,
                'reporting_period_start' => optional($selectedReport->reporting_period_start)->format('Y-m-d'),
                'reporting_period_end' => optional($selectedReport->reporting_period_end)->format('Y-m-d'),
                'generated_at' => optional($selectedReport->generated_at)->toIso8601String(),
                'status' => $selectedReport->status,
                'pdf_url' => route('label.reports.pdf', $selectedReport),
            ] : null,
            'previewReport' => $selectedReport?->snapshot_json,
            'profilesTableExists' => $profilesTableExists,
            'reportsTableExists' => $reportsTableExists,
            'tabLinks' => [
                'branding' => route('label.index'),
                'clients' => route('label.clients'),
                'reports' => route('label.reports'),
            ],
        ]);
    }

    private function storeGeneratedReport(
        Organization $organization,
        WhiteLabelReportProfile $profile,
        WhiteLabelReportBuilder $builder,
        array $overrides = [],
        ?WhiteLabelReport $existingReport = null
    ): WhiteLabelReport {
        $workingProfile = $profile->replicate();
        $workingProfile->id = $profile->id;
        $workingProfile->user_id = $profile->user_id;
        $workingProfile->organization_id = $profile->organization_id;
        $workingProfile->domain_id = $profile->domain_id;
        $workingProfile->report_title = $overrides['report_title'] ?: $profile->report_title;
        $workingProfile->reporting_period_start = $overrides['reporting_period_start'] ?: optional($profile->reporting_period_start)->format('Y-m-d');
        $workingProfile->reporting_period_end = $overrides['reporting_period_end'] ?: optional($profile->reporting_period_end)->format('Y-m-d');

        $snapshot = $builder->build($organization, $workingProfile);
        $payload = [
            'organization_id' => $organization->id,
            'user_id' => Auth::id(),
            'white_label_report_profile_id' => $profile->id,
            'domain_id' => $profile->domain_id,
            'client_name' => $profile->client_name,
            'client_website' => $profile->client_website,
            'report_title' => $workingProfile->report_title,
            'reporting_period_start' => $workingProfile->reporting_period_start,
            'reporting_period_end' => $workingProfile->reporting_period_end,
            'status' => 'ready',
            'generated_at' => now(),
            'snapshot_json' => $snapshot,
        ];

        if ($existingReport) {
            $existingReport->update($payload);

            return $existingReport->fresh();
        }

        return WhiteLabelReport::create($payload);
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
        if ($scopedOrganization instanceof Organization && ($scopedOrganization->hasUser($user) || $scopedOrganization->owner_user_id === $user->id)) {
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

    private function resolveOwnedReport(Request $request, WhiteLabelReport $report): WhiteLabelReport
    {
        $organization = $this->requireOrganization($request);

        abort_unless(
            $report->organization_id === $organization->id && $report->user_id === Auth::id(),
            403
        );

        return $report;
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

    private function ensureReportsTableExists(): void
    {
        abort_unless(
            Schema::hasTable('white_label_reports'),
            500,
            'The white_label_reports table is missing. Please run the latest migrations.'
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
            'intro_text' => $branding->report_intro_text ?? '',
            'outro_text' => $branding->report_outro_text ?? '',
            'report_period_days' => (int) ($branding->report_period_days ?: self::DEFAULTS['report_period_days']),
            'report_sections' => $branding->report_sections_json ?: self::DEFAULTS['report_sections'],
            'use_custom_cover_title' => (bool) $branding->use_custom_cover_title,
            'custom_cover_title' => $branding->custom_cover_title ?? '',
        ];
    }
}

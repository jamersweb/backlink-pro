<?php

namespace App\Http\Controllers;

/**
 * MARKETING FLOW SUMMARY (instant report):
 * - Marketing page: POST /audit -> AuditController::store()
 * - Creates Audit with pages_limit=1, crawl_depth=0, share_token
 * - Runs RunSeoAuditJob::dispatchSync($audit->id) so the full audit runs in the same request
 * - Redirects to GET /audit/{audit}?token=... (audit.show) which renders the report
 * - User lands on report page with data already loaded (no polling).
 *
 * USER AUDIT FLOW (same instant behavior):
 * - User form: POST /audit-report -> create() below
 * - Creates Audit (user_id, normalized_url), runs RunSeoAuditJob::dispatchSync (same as marketing)
 * - Redirects to GET /audit-report/{id} (report view page)
 * - Report is rendered on dedicated page only; form page has no report UI.
 */

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Models\Organization;
use App\Jobs\RunSeoAuditJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Services\Audit\ChromiumPdfRenderer;
use App\Services\Billing\PlanLimiter;
use App\Services\SeoAudit\CrawlModuleConfig;
use App\Services\SeoAudit\CustomAuditRulesValidator;
use Inertia\Inertia;

class AuditReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $organization = $user?->organizationUsers()->with('organization.plan')->first()?->organization;
        $planLimiter = app(PlanLimiter::class);
        
        $googleSeoAccount = ConnectedAccount::where('user_id', $user->id)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();
        
        $recentAudits = Audit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'url', 'status', 'overall_score', 'created_at', 'finished_at']);
        
        $lastCompleted = Audit::where('user_id', $user->id)
            ->where('status', Audit::STATUS_COMPLETED)
            ->orderBy('finished_at', 'desc')
            ->first(['id', 'url', 'finished_at']);
        
        return Inertia::render('AuditReport', [
            'googleConnected' => (bool) $googleSeoAccount,
            'googleEmail' => $googleSeoAccount?->email,
            'recentAudits' => $recentAudits,
            'lastCompletedAuditId' => $lastCompleted?->id,
            'canUseWhiteLabel' => $organization ? $planLimiter->canUseWhiteLabel($organization) : false,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'email' => 'nullable|email',
            'send_to_email' => 'boolean',
            'js_rendering_enabled' => 'nullable|boolean',
            'near_duplicate_enabled' => 'nullable|boolean',
            'spelling_grammar_enabled' => 'nullable|boolean',
            'custom_source_search_enabled' => 'nullable|boolean',
            'custom_extraction_enabled' => 'nullable|boolean',
            'forms_auth_enabled' => 'nullable|boolean',
            'segmentation_enabled' => 'nullable|boolean',
            'link_metrics_enabled' => 'nullable|boolean',
            'site_visualisation_enabled' => 'nullable|boolean',
            'spelling_allowlist' => 'nullable|array|max:200',
            'spelling_allowlist.*' => 'string|max:120',
            'custom_source_search_rules' => 'nullable|array',
            'custom_extraction_rules' => 'nullable|array',
            'forms_auth_login_url' => 'nullable|string|max:2048',
            'forms_auth_username' => 'nullable|string|max:255',
            'forms_auth_password' => 'nullable|string|max:512',
            'forms_auth_username_selector' => 'nullable|string|max:512',
            'forms_auth_password_selector' => 'nullable|string|max:512',
            'forms_auth_submit_selector' => 'nullable|string|max:512',
            'forms_auth_success_indicator' => 'nullable|string|max:512',
        ]);

        $user = Auth::user();
        $normalizedUrl = $this->normalizeUrl($validated['url']);

        $orgAllow = [];
        $org = null;
        if ($user) {
            $orgUser = $user->organizationUsers()->first();
            if ($orgUser) {
                $org = Organization::query()->find($orgUser->organization_id);
                $orgAllow = $org?->spelling_allowlist ?? [];
            }
        }
        $spellingAllow = array_values(array_unique(array_filter(array_map(
            'strtolower',
            array_map('trim', array_merge($orgAllow, $validated['spelling_allowlist'] ?? []))
        ))));

        $crawlModuleFlags = app(CrawlModuleConfig::class)->normalizeFlags($validated);

        if (! empty($crawlModuleFlags['forms_auth_enabled'])) {
            $login = trim((string) ($validated['forms_auth_login_url'] ?? ''));
            $fu = trim((string) ($validated['forms_auth_username'] ?? ''));
            $fp = (string) ($validated['forms_auth_password'] ?? '');
            if ($login === '' || $fu === '' || $fp === '') {
                return back()->withErrors([
                    'forms_auth_login_url' => 'Forms authentication requires login URL, username, and password.',
                ])->withInput();
            }
        }

        $customSearchRulesPayload = null;
        if (array_key_exists('custom_source_search_rules', $validated) && $validated['custom_source_search_rules'] !== null) {
            $v = CustomAuditRulesValidator::validateSearchPayload($validated['custom_source_search_rules']);
            if (! $v['valid']) {
                return back()->withErrors(['custom_source_search_rules' => implode(' ', $v['errors'])])->withInput();
            }
            if ($v['rules'] !== []) {
                $customSearchRulesPayload = ['rules' => $v['rules']];
            }
        }
        $customExtractionRulesPayload = null;
        if (array_key_exists('custom_extraction_rules', $validated) && $validated['custom_extraction_rules'] !== null) {
            $v = CustomAuditRulesValidator::validateExtractionPayload($validated['custom_extraction_rules']);
            if (! $v['valid']) {
                return back()->withErrors(['custom_extraction_rules' => implode(' ', $v['errors'])])->withInput();
            }
            if ($v['rules'] !== []) {
                $customExtractionRulesPayload = ['rules' => $v['rules']];
            }
        }

        $formsAuthPayload = [
            'forms_auth_login_url' => null,
            'forms_auth_username' => null,
            'forms_auth_password' => null,
            'forms_auth_username_selector' => null,
            'forms_auth_password_selector' => null,
            'forms_auth_submit_selector' => null,
            'forms_auth_success_indicator' => null,
        ];
        if (! empty($crawlModuleFlags['forms_auth_enabled'])) {
            $formsAuthPayload = [
                'forms_auth_login_url' => $validated['forms_auth_login_url'] ?? null,
                'forms_auth_username' => $validated['forms_auth_username'] ?? null,
                'forms_auth_password' => $validated['forms_auth_password'] ?? null,
                'forms_auth_username_selector' => $validated['forms_auth_username_selector'] ?? null,
                'forms_auth_password_selector' => $validated['forms_auth_password_selector'] ?? null,
                'forms_auth_submit_selector' => $validated['forms_auth_submit_selector'] ?? null,
                'forms_auth_success_indicator' => $validated['forms_auth_success_indicator'] ?? null,
            ];
        }

        $audit = Audit::create($this->filterAuditCreatePayload([
            'user_id' => $user->id,
            'organization_id' => $org?->id,
            'url' => $validated['url'],
            'normalized_url' => $normalizedUrl,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_AUTH,
            'lead_email' => $validated['send_to_email'] ? $validated['email'] : null,
            'share_token' => Str::random(32),
            'pages_limit' => 1,
            'crawl_depth' => 0,
            'started_at' => now(),
            'progress_percent' => 0,
            'crawl_module_flags' => $crawlModuleFlags,
            'spelling_allowlist' => $spellingAllow !== [] ? $spellingAllow : null,
            'custom_source_search_rules' => $customSearchRulesPayload,
            'custom_extraction_rules' => $customExtractionRulesPayload,
            ...$formsAuthPayload,
        ]));
        
        \Log::info('User audit created', [
            'audit_id' => $audit->id,
            'url' => $audit->url,
            'user_id' => $user->id,
        ]);
        
        // Same as marketing: run audit synchronously so report is ready before redirect (instant report).
        try {
            @set_time_limit(120);
            RunSeoAuditJob::dispatchSync($audit->id);
        } catch (\Throwable $e) {
            \Log::error('Audit run failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $audit->refresh();
            if ($audit->status !== Audit::STATUS_COMPLETED) {
                $audit->status = Audit::STATUS_FAILED;
                $audit->error = $e->getMessage();
                $audit->finished_at = now();
                $audit->save();
            }
        }

        // Email after report is stored (do not block display)
        if ($audit->lead_email && $audit->status === Audit::STATUS_COMPLETED) {
            try {
                \Illuminate\Support\Facades\Mail::to($audit->lead_email)
                    ->queue(new \App\Mail\UserAuditReadyMail($audit->fresh()));
                \Log::info('Audit email queued', ['audit_id' => $audit->id, 'to' => $audit->lead_email]);
            } catch (\Exception $mailEx) {
                \Log::warning('Audit email queue failed', ['audit_id' => $audit->id, 'error' => $mailEx->getMessage()]);
            }
        }

        // Redirect to dedicated report page (no report on form page).
        if ($request->header('X-Inertia') || $request->wantsJson() === false) {
            return redirect()->route('audit-report.show', ['id' => $audit->id]);
        }
        return response()->json([
            'success' => true,
            'audit_id' => $audit->id,
            'redirect' => route('audit-report.show', ['id' => $audit->id]),
            'status' => $audit->fresh()->status,
        ]);
    }

    public function show(Request $request, $id)
    {
        $audit = Audit::with(['pages', 'issues', 'organization.brandingProfile'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $payload = ['audit' => $this->formatAuditForFrontend($audit)];

        // Inertia requests must receive an Inertia response (page or redirect), never plain JSON.
        if ($request->header('X-Inertia')) {
            return Inertia::render('AuditReportView', $payload);
        }

        // API / fetch with Accept: application/json
        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('AuditReportView', $payload);
    }

    public function status($id)
    {
        $audit = Audit::where('user_id', Auth::id())
            ->findOrFail($id);
        
        return response()->json([
            'id' => $audit->id,
            'status' => $audit->status,
            'progress_percent' => $audit->progress_percent ?? 0,
            'progress_stage' => $audit->progress_stage ?? null,
            'overall_score' => $audit->overall_score,
            'has_report' => $audit->status === Audit::STATUS_COMPLETED,
            'psi_ready_at' => optional($audit->psi_ready_at)->toIso8601String(),
            'ga4_ready_at' => optional($audit->ga4_ready_at)->toIso8601String(),
            'gsc_ready_at' => optional($audit->gsc_ready_at)->toIso8601String(),
            'started_at' => $audit->started_at?->toIso8601String(),
            'finished_at' => $audit->finished_at?->toIso8601String(),
            'created_at' => $audit->created_at?->toIso8601String(),
            'updated_at' => $audit->updated_at?->toIso8601String(),
            'error' => $audit->error,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function share(Request $request, $token)
    {
        $audit = Audit::with(['pages', 'issues', 'organization.brandingProfile'])
            ->where('share_token', $token)
            ->where('status', Audit::STATUS_COMPLETED)
            ->firstOrFail();

        $payload = [
            'audit' => $this->formatAuditForFrontend($audit),
            'isShared' => true,
        ];

        // Inertia requests must receive an Inertia response, never plain JSON.
        if ($request->header('X-Inertia')) {
            return Inertia::render('AuditReportView', $payload);
        }

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('AuditReportView', $payload);
    }

    protected function formatAuditForFrontend(Audit $audit): array
    {
        $kpis = $audit->audit_kpis ?? [];
        $page = $audit->pages->first();
        
        return [
            'id' => $audit->id,
            'organization_id' => $audit->organization_id,
            'url' => $audit->url,
            'normalized_url' => $audit->normalized_url,
            'status' => $audit->status,
            'overall_score' => $audit->overall_score,
            'overall_grade' => $audit->overall_grade,
            'category_scores' => $audit->category_scores,
            'category_grades' => $audit->category_grades,
            'summary' => $audit->summary,
            'share_token' => $audit->share_token,
            'created_at' => $audit->created_at?->toIso8601String(),
            'started_at' => $audit->started_at?->toIso8601String(),
            'finished_at' => $audit->finished_at?->toIso8601String(),
            'error' => $audit->error,
            'progress_percent' => $audit->progress_percent,
            'crawl_module_flags' => $audit->crawl_module_flags ?? [],
            'report_modules' => $audit->report_modules ?? null,
            'psi_ready_at' => optional($audit->psi_ready_at)->toIso8601String(),
            'ga4_ready_at' => optional($audit->ga4_ready_at)->toIso8601String(),
            'gsc_ready_at' => optional($audit->gsc_ready_at)->toIso8601String(),

            // On-page data from AuditPage
            'page_data' => $page ? [
                'title' => $page->title,
                'title_len' => $page->title_len,
                'meta_description' => $page->meta_description,
                'meta_len' => $page->meta_len,
                'h1_count' => $page->h1_count,
                'h2_count' => $page->h2_count,
                'h3_count' => $page->h3_count,
                'word_count' => $page->word_count,
                'images_total' => $page->images_total,
                'images_missing_alt' => $page->images_missing_alt,
                'internal_links_count' => $page->internal_links_count,
                'external_links_count' => $page->external_links_count,
                'og_present' => $page->og_present,
                'twitter_cards_present' => $page->twitter_cards_present,
                'schema_types' => $page->schema_types,
                'html_size_bytes' => $page->html_size_bytes,
                'status_code' => $page->status_code,
                'canonical_url' => $page->canonical_url,
                'robots_meta' => $page->robots_meta,
                'lighthouse_mobile' => $page->lighthouse_mobile,
                'lighthouse_desktop' => $page->lighthouse_desktop,
                'performance_metrics' => $page->performance_metrics,
                'security_headers' => $page->security_headers,
                'link_metrics_json' => $page->link_metrics_json,
            ] : null,
            
            // Issues (severity for AuditReportView: high->critical, medium->warning, low->info)
            'issues' => $audit->issues->map(fn($i) => [
                'id' => $i->id,
                'audit_run_id' => $i->audit_run_id ?? $i->audit_id,
                'url' => $i->url,
                'module_key' => $i->module_key,
                'issue_type' => $i->issue_type ?? $i->code,
                'status' => $i->status,
                'message' => $i->message ?? $i->title,
                'details_json' => $i->details_json,
                'discovered_at' => optional($i->discovered_at)->toIso8601String(),
                'code' => $i->code,
                'category' => $i->category ?? 'general',
                'title' => $i->title,
                'description' => $i->description,
                'impact' => $i->impact,
                'severity' => $i->severity ?: match ($i->impact ?? '') { 'high' => 'critical', 'medium' => 'warning', 'low' => 'info', default => 'info' },
                'effort' => $i->effort,
                'score_penalty' => $i->score_penalty,
                'affected_count' => $i->affected_count,
                'recommendation' => $i->recommendation,
                'fix_steps' => $i->fix_steps,
            ])->toArray(),
            
            // KPI data (PSI, GA4, GSC)
            'kpis' => $kpis,
            
            // PSI shortcuts
            'psi' => \Illuminate\Support\Arr::get($kpis, 'google.pagespeed'),
            
            // GA4 data
            'ga4' => $kpis['ga4'] ?? null,
            
            // GSC data
            'gsc' => $kpis['gsc'] ?? null,
            'branding' => $this->formatBrandingForFrontend($audit),
        ];
    }

    protected function formatBrandingForFrontend(Audit $audit): ?array
    {
        $branding = $audit->organization?->brandingProfile;

        if (! $branding || ! $branding->white_label_enabled) {
            return null;
        }

        return [
            'enabled' => true,
            'company_name' => $branding->brand_name ?: ($audit->organization?->name ?? null),
            'website' => $branding->website ?: $audit->normalized_url,
            'footer_text' => $branding->report_footer_text,
            'logo_url' => $branding->logo_path ? Storage::disk('public')->url($branding->logo_path) : null,
            'report_period_days' => (int) ($branding->report_period_days ?: 30),
            'report_sections' => $branding->report_sections_json ?: [],
            'use_custom_cover_title' => (bool) $branding->use_custom_cover_title,
            'custom_cover_title' => $branding->custom_cover_title,
        ];
    }

    public function exportPdf(Request $request, $id)
    {
        $audit = Audit::with(['pages', 'issues'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $page = $audit->pages()->first();
        $issues = $audit->issues()
            ->orderByRaw("CASE impact WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('score_penalty', 'desc')
            ->get();

        $auditUi = $this->formatAuditForFrontend($audit);
        $filename = 'seo-audit-' . $audit->id . '-' . date('Y-m-d') . '.pdf';

        try {
            $html = View::make('audit.pdf_chromium', [
                'audit' => $audit,
                'page' => $page,
                'issues' => $issues,
                'auditUi' => $auditUi,
            ])->render();
        } catch (\Throwable $e) {
            \Log::error('audit PDF (Chromium) view render failed', [
                'audit_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Could not build PDF.');
        }

        try {
            $binary = app(ChromiumPdfRenderer::class)->htmlToPdf($html);

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Length' => (string) strlen($binary),
                'Cache-Control' => 'private, no-store, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Chromium PDF export failed; falling back to DomPDF', [
                'audit_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $legacyHtml = View::make('audit.pdf', [
                'audit' => $audit,
                'page' => $page,
                'issues' => $issues,
                'auditUi' => $auditUi,
            ])->render();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($legacyHtml)->setPaper('A4');
            if (method_exists($pdf, 'setOption')) {
                $pdf->setOption('isRemoteEnabled', true);
            }

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::warning('DomPDF fallback failed, returning HTML', ['audit_id' => $id, 'error' => $e->getMessage()]);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'inline; filename="audit-' . $audit->id . '.html"',
            ]);
        }
    }

    private function normalizeUrl($url)
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }

    private function filterAuditCreatePayload(array $payload): array
    {
        static $columns = null;

        $columns ??= array_flip(Schema::getColumnListing('audits'));

        return array_filter(
            $payload,
            static fn (string $key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }
}

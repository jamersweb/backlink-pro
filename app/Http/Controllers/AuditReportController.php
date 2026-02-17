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
use App\Jobs\RunSeoAuditJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuditReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
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
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'email' => 'nullable|email',
            'send_to_email' => 'boolean',
        ]);

        $user = Auth::user();
        $normalizedUrl = $this->normalizeUrl($validated['url']);
        
        $audit = Audit::create([
            'user_id' => $user->id,
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
        ]);
        
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
        $audit = Audit::with(['pages', 'issues'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'audit' => $this->formatAuditForFrontend($audit),
            ]);
        }
        
        return Inertia::render('AuditReportView', [
            'audit' => $this->formatAuditForFrontend($audit),
        ]);
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

    public function share($token)
    {
        $audit = Audit::with(['pages', 'issues'])
            ->where('share_token', $token)
            ->where('status', Audit::STATUS_COMPLETED)
            ->firstOrFail();
        
        if (request()->wantsJson()) {
            return response()->json([
                'audit' => $this->formatAuditForFrontend($audit),
            ]);
        }
        
        return Inertia::render('AuditReportView', [
            'audit' => $this->formatAuditForFrontend($audit),
            'isShared' => true,
        ]);
    }

    protected function formatAuditForFrontend(Audit $audit): array
    {
        $kpis = $audit->audit_kpis ?? [];
        $page = $audit->pages->first();
        
        return [
            'id' => $audit->id,
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
            ] : null,
            
            // Issues (severity for AuditReportView: high->critical, medium->warning, low->info)
            'issues' => $audit->issues->map(fn($i) => [
                'id' => $i->id,
                'code' => $i->code,
                'category' => $i->category ?? 'general',
                'title' => $i->title,
                'description' => $i->description,
                'impact' => $i->impact,
                'severity' => match ($i->impact ?? '') { 'high' => 'critical', 'medium' => 'warning', 'low' => 'info', default => 'info' },
                'effort' => $i->effort,
                'score_penalty' => $i->score_penalty,
                'affected_count' => $i->affected_count,
                'recommendation' => $i->recommendation,
                'fix_steps' => $i->fix_steps,
            ])->toArray(),
            
            // KPI data (PSI, GA4, GSC)
            'kpis' => $kpis,
            
            // PSI shortcuts
            'psi' => $kpis['google']['pagespeed'] ?? null,
            
            // GA4 data
            'ga4' => $kpis['ga4'] ?? null,
            
            // GSC data
            'gsc' => $kpis['gsc'] ?? null,
        ];
    }

    public function exportPdf(Request $request, $id)
    {
        $audit = Audit::with(['pages', 'issues'])
            ->where('user_id', Auth::id())
            ->where('status', Audit::STATUS_COMPLETED)
            ->findOrFail($id);

        $page = $audit->pages()->first();
        $issues = $audit->issues()
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('score_penalty', 'desc')
            ->get();

        $html = View::make('audit.pdf', [
            'audit' => $audit,
            'page' => $page,
            'issues' => $issues,
        ])->render();

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4');
            if (method_exists($pdf, 'setOption')) {
                $pdf->setOption('isRemoteEnabled', true);
            }
            $filename = 'seo-audit-' . $audit->id . '-' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::warning('PDF export failed, returning HTML', ['audit_id' => $id, 'error' => $e->getMessage()]);
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
}

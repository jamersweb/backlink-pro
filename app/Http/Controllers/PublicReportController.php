<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ReportAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PublicReportController extends Controller
{
    /**
     * Show public report (gated or full)
     */
    public function show(string $token, Request $request)
    {
        $audit = Audit::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();


        $unlockToken = $request->query('unlock_token');

        // Check if unlocked
        $isUnlocked = false;
        if ($unlockToken) {
            $accessToken = ReportAccessToken::verify($unlockToken, $audit);
            if ($accessToken) {
                $isUnlocked = true;
                // Mark as used (single-use)
                $accessToken->markAsUsed();
            }
        }

        // If gated and not unlocked, show summary only
        if ($audit->is_gated && !$isUnlocked) {
            return Inertia::render('Audit/ShowGated', [
                'audit' => [
                    'id' => $audit->id,
                    'url' => $audit->url,
                    'public_summary' => $audit->public_summary ?? $this->generatePublicSummary($audit),
                    'is_gated' => true,
                    'share_token' => $token,
                ],
            ]);
        }

        // Show full report
        $page = $audit->pages()->first();
        $issues = $audit->issues()
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('score_penalty', 'desc')
            ->limit(20)
            ->get();

        $pagespeedKpis = data_get($audit->audit_kpis, 'google.pagespeed');

        return Inertia::render('Audit/Show', [
            'audit' => [
                'id' => $audit->id,
                'url' => $audit->url,
                'normalized_url' => $audit->normalized_url,
                'status' => $audit->status,
                'overall_score' => $audit->overall_score,
                'overall_grade' => $audit->overall_grade,
                'category_scores' => $audit->category_scores,
                'summary' => $audit->summary,
                'audit_kpis' => $audit->audit_kpis,
                'is_public' => true,
                'share_token' => $token,
            ],
            'google' => [
                'connected' => false,
                'pagespeed_configured' => (bool) $pagespeedKpis
                    || (bool) config('services.google.pagespeed_api_key'),
                'pagespeed' => $pagespeedKpis,
                'ga4' => null,
                'gsc' => null,
            ],
            'page' => $page ? [
                'id' => $page->id,
                'url' => $page->url,
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
            ] : null,
            'issues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'code' => $issue->code,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'impact' => $issue->impact,
                    'effort' => $issue->effort,
                    'score_penalty' => $issue->score_penalty,
                    'recommendation' => $issue->recommendation,
                    'fix_steps' => $issue->fix_steps,
                ];
            }),
            'isOwner' => false,
            'shareUrl' => route('public.report.show', $token),
        ]);
    }

    /**
     * Get public summary (API endpoint)
     */
    public function summary(string $token)
    {
        $audit = Audit::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        return response()->json([
            'public_summary' => $audit->public_summary ?? $this->generatePublicSummary($audit),
            'is_gated' => $audit->is_gated,
        ]);
    }

    /**
     * Request unlock (send magic link)
     */
    public function unlock(Request $request, string $token)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $audit = Audit::where('share_token', $token)
            ->where('is_public', true)
            ->where('is_gated', true)
            ->firstOrFail();

        // Generate access token
        $rawToken = ReportAccessToken::generate($audit, $validated['email']);

        // Send email with magic link
        $unlockUrl = route('public.report.show', [
            'token' => $token,
            'unlock_token' => $rawToken,
        ]);

        Mail::to($validated['email'])->send(new \App\Mail\UnlockReportMail($audit, $unlockUrl));

        return response()->json([
            'message' => 'Unlock link sent to your email',
        ]);
    }

    /**
     * Generate public summary from audit
     */
    protected function generatePublicSummary(Audit $audit): array
    {
        $topIssues = $audit->issues()
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('score_penalty', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($issue) => [
                'title' => $issue->title,
                'impact' => $issue->impact,
            ])
            ->toArray();

        $performanceMetrics = null;
        if ($audit->performance_summary) {
            $perf = $audit->performance_summary;
            $performanceMetrics = [
                'mobile_score' => $perf['mobile_avg_score'] ?? null,
                'desktop_score' => $perf['desktop_avg_score'] ?? null,
                'worst_lcp' => $perf['worst_lcp'] ?? null,
            ];
        }

        return [
            'overall_score' => $audit->overall_score,
            'overall_grade' => $audit->overall_grade,
            'category_scores' => $audit->category_scores,
            'top_issues' => $topIssues,
            'performance_metrics' => $performanceMetrics,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditCustomExtractionResult;
use App\Models\AuditCustomSearchResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\SeoAudit\ReportModuleBuilder;

class AuditExportController extends Controller
{
    /**
     * Export audit as PDF
     */
    public function pdf(Audit $audit, Request $request)
    {
        // Check authorization (same rules as show)
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Load relations
        $page = $audit->pages()->first();
        $issues = $audit->issues()
            ->orderByRaw("CASE impact WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('score_penalty', 'desc')
            ->get();

        // Check plan limit for PDF export
        if ($audit->organization_id) {
            $planLimiter = new \App\Services\Billing\PlanLimiter();
            $organization = $audit->organization;
            
            if (!$planLimiter->canExportPdf($organization)) {
                // Free plan: return watermarked HTML or show upgrade prompt
                return $this->exportAsHtml($audit, $page, $issues);
            }
            
            // Record usage: pdf_export
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_PDF_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'pdf']
            );
        }

        $html = $this->renderPdfHtml($audit, $page, $issues, $request, 'audit.pdf_v2');

        $shouldDownload = $request->boolean('download');
        if ($shouldDownload) {
            if (class_exists(Pdf::class)) {
                try {
                    return $this->exportPdfWithDompdf($audit, $html);
                } catch (\Throwable $e) {
                    // Fall through to Browsershot if Dompdf fails.
                }
            }
            if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
                try {
                    return $this->exportPdfFromHtml($audit, $html);
                } catch (\Throwable $e) {
                    // Continue to HTML fallback.
                }
            }
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Export-Notice' => 'PDF engine unavailable, showing HTML instead.',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Export pages as CSV
     */
    public function pagesCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Record usage: csv_export
        if ($audit->organization_id) {
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_CSV_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'pages']
            );
        }

        $pages = $audit->pages()->get();

        $filename = 'audit-' . $audit->id . '-pages-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($pages) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'URL',
                'Status Code',
                'Title',
                'Title Length',
                'Meta Description',
                'Meta Length',
                'H1 Count',
                'H2 Count',
                'H3 Count',
                'Word Count',
                'Images Total',
                'Images Missing Alt',
                'Internal Links',
                'External Links',
            ]);

            // Data
            foreach ($pages as $page) {
                fputcsv($file, [
                    $page->url,
                    $page->status_code ?? '',
                    $page->title ?? '',
                    $page->title_len ?? 0,
                    $page->meta_description ?? '',
                    $page->meta_len ?? 0,
                    $page->h1_count,
                    $page->h2_count,
                    $page->h3_count,
                    $page->word_count,
                    $page->images_total,
                    $page->images_missing_alt,
                    $page->internal_links_count,
                    $page->external_links_count,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export issues as CSV
     */
    public function issuesCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Record usage: csv_export
        if ($audit->organization_id) {
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_CSV_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'issues']
            );
        }

        $issues = $audit->issues()
            ->orderByRaw("CASE impact WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('score_penalty', 'desc')
            ->get();

        $filename = 'audit-' . $audit->id . '-issues-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($issues) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Code',
                'Title',
                'Description',
                'Impact',
                'Effort',
                'Score Penalty',
                'Affected Count',
                'Recommendation',
            ]);

            foreach ($issues as $issue) {
                fputcsv($file, [
                    $issue->code,
                    $issue->title,
                    $issue->description,
                    $issue->impact,
                    $issue->effort,
                    $issue->score_penalty,
                    $issue->affected_count,
                    $issue->recommendation ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export links as CSV
     */
    public function linksCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Record usage: csv_export
        if ($audit->organization_id) {
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_CSV_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'links']
            );
        }

        $links = $audit->links()->get();

        $filename = 'audit-' . $audit->id . '-links-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($links) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'From URL',
                'To URL',
                'Type',
                'Status Code',
                'Final URL',
                'Redirect Hops',
                'Is Broken',
                'Rel Nofollow',
                'Anchor Text',
                'Error',
            ]);

            foreach ($links as $link) {
                fputcsv($file, [
                    $link->from_url,
                    $link->to_url,
                    $link->type,
                    $link->status_code ?? '',
                    $link->final_url ?? '',
                    $link->redirect_hops,
                    $link->is_broken ? 'Yes' : 'No',
                    $link->rel_nofollow ? 'Yes' : 'No',
                    $link->anchor_text ?? '',
                    $link->error ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export broken links as CSV
     */
    public function brokenLinksCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Record usage: csv_export
        if ($audit->organization_id) {
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_CSV_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'broken_links']
            );
        }

        $brokenLinks = $audit->links()
            ->where('is_broken', true)
            ->get();

        $filename = 'audit-' . $audit->id . '-broken-links-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($brokenLinks) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'From URL',
                'To URL',
                'Type',
                'Status Code',
                'Error',
            ]);

            foreach ($brokenLinks as $link) {
                fputcsv($file, [
                    $link->from_url,
                    $link->to_url,
                    $link->type,
                    $link->status_code ?? '',
                    $link->error ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export using Browsershot
     */
    protected function exportPdfFromHtml(Audit $audit, string $html): \Illuminate\Http\Response
    {
        $pdf = \Spatie\Browsershot\Browsershot::html($html)
            ->setOption('printBackground', true)
            ->pdf();

        $filename = 'seo-audit-' . $audit->id . '-' . date('Y-m-d') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function exportPdfWithDompdf(Audit $audit, string $html): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadHTML($html)->setPaper('A4');
        if (method_exists($pdf, 'setOption')) {
            $pdf->setOption('isRemoteEnabled', true);
        }
        $filename = 'seo-audit-' . $audit->id . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export as HTML (fallback)
     */
    protected function exportAsHtml(Audit $audit, $page, $issues): \Illuminate\Http\Response
    {
        $html = View::make('audit.pdf', [
            'audit' => $audit,
            'page' => $page,
            'issues' => $issues,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    protected function renderPdfHtml(Audit $audit, $page, $issues, Request $request, string $view): string
    {
        if ($view === 'audit.pdf_v2') {
            $pages = $audit->pages()->get();
            $topPages = $pages->whereNotNull('performance_metrics')
                ->sortByDesc(fn($p) => $p->performance_metrics['mobile']['score'] ?? 0)
                ->take(5);

            $branding = null;
            $hideBranding = false;
            $logoUrl = null;

            if ($audit->organization_id) {
                $branding = $audit->organization->brandingProfile;
                if ($branding) {
                    $hideBranding = $branding->hide_backlinkpro_branding;
                    if ($branding->logo_path) {
                        $logoUrl = asset('storage/' . $branding->logo_path);
                    }
                }
            }

            if ($request->query('brand') === 'client') {
                $hideBranding = true;
            }
            if ($request->query('logo_url')) {
                $logoUrl = $request->query('logo_url');
            }

            return View::make('audit.pdf_v2', [
                'audit' => $audit,
                'pages' => $pages,
                'topPages' => $topPages,
                'issues' => $issues,
                'hideBranding' => $hideBranding,
                'logoUrl' => $logoUrl,
                'branding' => $branding,
            ])->render();
        }

        return View::make('audit.pdf', [
            'audit' => $audit,
            'page' => $page,
            'issues' => $issues,
        ])->render();
    }

    /**
     * Export Lighthouse JSON (homepage)
     */
    public function lighthouseJson(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        $homepage = $audit->pages()
            ->where('url', $audit->normalized_url)
            ->first();

        if (!$homepage || !$homepage->lighthouse_mobile) {
            abort(404, 'Lighthouse data not available for homepage.');
        }

        $filename = 'audit-' . $audit->id . '-lighthouse-' . date('Y-m-d') . '.json';
        
        return response()->json([
            'mobile' => $homepage->lighthouse_mobile,
            'desktop' => $homepage->lighthouse_desktop,
            'performance_metrics' => $homepage->performance_metrics,
        ], 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export assets as CSV
     */
    public function assetsCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Record usage: csv_export
        if ($audit->organization_id) {
            \App\Services\Billing\UsageRecorder::record(
                $audit->organization_id,
                \App\Models\UsageEvent::TYPE_CSV_EXPORT,
                1,
                $audit->id,
                ['export_type' => 'assets']
            );
        }

        $assets = $audit->assets()
            ->orderBy('size_bytes', 'desc')
            ->get();

        $filename = 'audit-' . $audit->id . '-assets-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($assets) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Page URL',
                'Asset URL',
                'Type',
                'Size (Bytes)',
                'Size (Human)',
                'Status Code',
                'Content Type',
                'Third Party',
            ]);

            foreach ($assets as $asset) {
                $sizeHuman = $this->formatBytes($asset->size_bytes ?? 0);
                
                fputcsv($file, [
                    $asset->page_url,
                    $asset->asset_url,
                    $asset->type,
                    $asset->size_bytes ?? 0,
                    $sizeHuman,
                    $asset->status_code ?? '',
                    $asset->content_type ?? '',
                    $asset->is_third_party ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Export PDF v2 (white-label professional report)
     */
    public function pdfV2(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        // Load relations
        $pages = $audit->pages()->get();
        $issues = $audit->issues()
            ->orderByRaw("CASE impact WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('score_penalty', 'desc')
            ->get();
        
        $topPages = $pages->whereNotNull('performance_metrics')
            ->sortByDesc(fn($p) => $p->performance_metrics['mobile']['score'] ?? 0)
            ->take(5);

        // Get branding from organization or request
        $branding = null;
        $hideBranding = false;
        $logoUrl = null;
        
        if ($audit->organization_id) {
            $branding = $audit->organization->brandingProfile;
            if ($branding) {
                $hideBranding = $branding->hide_backlinkpro_branding;
                if ($branding->logo_path) {
                    $logoUrl = asset('storage/' . $branding->logo_path);
                }
            }
        }
        
        // Override with query params if provided
        if ($request->query('brand') === 'client') {
            $hideBranding = true;
        }
        if ($request->query('logo_url')) {
            $logoUrl = $request->query('logo_url');
        }

        $html = View::make('audit.pdf_v2', [
            'audit' => $audit,
            'pages' => $pages,
            'topPages' => $topPages,
            'issues' => $issues,
            'hideBranding' => $hideBranding,
            'logoUrl' => $logoUrl,
            'branding' => $branding,
        ])->render();

        // Check if Browsershot is available
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            $pdf = \Spatie\Browsershot\Browsershot::html($html)
                ->setOption('printBackground', true)
                ->setOption('format', 'A4')
                ->pdf();

            $filename = 'seo-audit-' . $audit->id . '-' . date('Y-m-d') . '.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // Fallback: return HTML view
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Export normalized module report as JSON.
     */
    public function modulesJson(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        $modules = $audit->report_modules ?: app(ReportModuleBuilder::class)->build($audit);
        $moduleKey = $request->query('module_key');
        if ($moduleKey) {
            $modules['modules'] = collect($modules['modules'] ?? [])
                ->filter(fn ($module) => ($module['module_key'] ?? null) === $moduleKey)
                ->values()
                ->toArray();
        }

        $filename = 'audit-' . $audit->id . '-modules-' . date('Y-m-d') . '.json';
        return response()->json($modules, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export normalized module report as CSV.
     */
    public function modulesCsv(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            abort(403, 'You do not have permission to export this audit.');
        }

        $modules = $audit->report_modules ?: app(ReportModuleBuilder::class)->build($audit);
        $moduleKey = $request->query('module_key');
        $rows = collect($modules['modules'] ?? []);
        if ($moduleKey) {
            $rows = $rows->filter(fn ($module) => ($module['module_key'] ?? null) === $moduleKey);
        }

        $filename = 'audit-' . $audit->id . '-modules-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        if ($moduleKey === 'js_rendering' && $rows->count() === 1) {
            $module = $rows->first();
            $issues = $module['issues'] ?? [];

            $callback = function () use ($issues) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'URL',
                    'Diff Type',
                    'Severity',
                    'Impact',
                    'Issue Code',
                    'Raw (JSON)',
                    'Rendered (JSON)',
                    'Recommendation',
                ]);

                foreach ($issues as $issue) {
                    $details = $issue['details_json'] ?? [];
                    fputcsv($file, [
                        $issue['url'] ?? '',
                        $details['diff_type'] ?? '',
                        $issue['severity'] ?? ($details['severity'] ?? ''),
                        $issue['impact'] ?? '',
                        $issue['code'] ?? ($issue['issue_type'] ?? ''),
                        json_encode($details['raw'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($details['rendered'] ?? [], JSON_UNESCAPED_UNICODE),
                        $issue['recommendation'] ?? '',
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        if ($moduleKey === 'spelling_grammar' && $rows->count() === 1) {
            $module = $rows->first();
            $issues = $module['issues'] ?? [];

            $callback = function () use ($issues) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'URL',
                    'Issue Kind',
                    'Issue Text',
                    'Suggested Correction',
                    'Confidence',
                    'Context Snippet',
                    'Severity',
                    'Impact',
                    'Recommendation',
                ]);

                foreach ($issues as $issue) {
                    $details = $issue['details_json'] ?? [];
                    fputcsv($file, [
                        $issue['url'] ?? '',
                        $details['issue_kind'] ?? '',
                        $details['issue_text'] ?? '',
                        $details['suggested_correction'] ?? '',
                        $details['confidence'] ?? '',
                        $details['context_snippet'] ?? '',
                        $issue['severity'] ?? '',
                        $issue['impact'] ?? '',
                        $issue['recommendation'] ?? '',
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        if ($moduleKey === 'forms_auth_summary' && $rows->count() === 1) {
            $module = $rows->first();
            $issues = $module['issues'] ?? [];

            $callback = function () use ($issues) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'URL',
                    'Issue Code',
                    'Severity',
                    'Message',
                    'Impact',
                    'Recommendation',
                ]);
                foreach ($issues as $issue) {
                    fputcsv($file, [
                        $issue['url'] ?? '',
                        $issue['code'] ?? ($issue['issue_type'] ?? ''),
                        $issue['severity'] ?? '',
                        $issue['message'] ?? '',
                        $issue['impact'] ?? '',
                        $issue['recommendation'] ?? '',
                    ]);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        if ($moduleKey === 'custom_source_search' && $rows->count() === 1) {
            $callback = function () use ($audit) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'URL',
                    'Rule Key',
                    'Rule Name',
                    'Target Scope',
                    'Match Type',
                    'Expect Match',
                    'Matched',
                    'Match Count',
                    'Sample Match',
                    'Severity',
                    'Error',
                    'Segment',
                ]);
                foreach (
                    AuditCustomSearchResult::query()
                        ->where('audit_id', $audit->id)
                        ->orderBy('rule_key')
                        ->orderBy('url')
                        ->cursor() as $r
                ) {
                    fputcsv($file, [
                        $r->url,
                        $r->rule_key,
                        $r->rule_name,
                        $r->target_scope,
                        $r->match_type,
                        $r->expect_match ? 'yes' : 'no',
                        $r->matched ? 'yes' : 'no',
                        $r->match_count,
                        $r->sample_match,
                        $r->severity,
                        $r->error_message,
                        $r->segment_key,
                    ]);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        if ($moduleKey === 'custom_extraction' && $rows->count() === 1) {
            $callback = function () use ($audit) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'URL',
                    'Rule Key',
                    'Rule Name',
                    'Target Scope',
                    'Extraction Type',
                    'Extractor',
                    'Values JSON',
                    'Missing',
                    'Error',
                    'Segment',
                    'Fingerprint',
                ]);
                foreach (
                    AuditCustomExtractionResult::query()
                        ->where('audit_id', $audit->id)
                        ->orderBy('rule_key')
                        ->orderBy('url')
                        ->cursor() as $r
                ) {
                    fputcsv($file, [
                        $r->url,
                        $r->rule_key,
                        $r->rule_name,
                        $r->target_scope,
                        $r->extraction_type,
                        $r->extractor,
                        json_encode($r->values ?? [], JSON_UNESCAPED_UNICODE),
                        $r->missing ? 'yes' : 'no',
                        $r->error_message,
                        $r->segment_key,
                        $r->fingerprint,
                    ]);
                }
                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        if ($moduleKey === 'link_metrics' && $rows->count() === 1) {
            $module = $rows->first();
            $issues = $module['issues'] ?? [];

            $callback = function () use ($module, $issues) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Row kind',
                    'URL',
                    'Issue type',
                    'Source module',
                    'Severity',
                    'Ref domains',
                    'Backlinks',
                    'Authority',
                    'Equity tier',
                    'Score penalty',
                    'Message',
                ]);

                foreach ($issues as $issue) {
                    $eq = $issue['details_json']['link_equity'] ?? [];
                    fputcsv($file, [
                        'issue',
                        $issue['url'] ?? '',
                        $issue['issue_type'] ?? '',
                        $issue['module_key'] ?? '',
                        $issue['severity'] ?? '',
                        $eq['referring_domains'] ?? '',
                        $eq['backlinks'] ?? '',
                        $eq['authority_score'] ?? '',
                        $eq['tier'] ?? '',
                        $issue['score_penalty'] ?? '',
                        $issue['message'] ?? '',
                    ]);
                }

                foreach ($module['tables'] ?? [] as $table) {
                    $tableRows = $table['rows'] ?? [];
                    if (! is_array($tableRows) || $tableRows === []) {
                        continue;
                    }
                    fputcsv($file, []);
                    fputcsv($file, ['table', $table['key'] ?? '', $table['title'] ?? '']);
                    $first = $tableRows[0] ?? [];
                    if (is_array($first)) {
                        fputcsv($file, array_keys($first));
                        foreach ($tableRows as $r) {
                            if (is_array($r)) {
                                fputcsv($file, array_values($r));
                            }
                        }
                    }
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Module Key',
                'Module Title',
                'Overview Count',
                'Affected URLs',
                'Critical',
                'Warning',
                'Info',
                'Top Recommendations',
            ]);

            foreach ($rows as $module) {
                $severity = $module['severity_counts'] ?? [];
                $card = $module['card'] ?? [];
                fputcsv($file, [
                    $module['module_key'] ?? '',
                    $module['module_title'] ?? '',
                    $card['overview_count'] ?? 0,
                    $card['affected_urls'] ?? 0,
                    $severity['critical'] ?? 0,
                    $severity['warning'] ?? 0,
                    $severity['info'] ?? 0,
                    implode(' | ', array_slice($module['recommendations'] ?? [], 0, 3)),
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}

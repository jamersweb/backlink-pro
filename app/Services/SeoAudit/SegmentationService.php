<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use Illuminate\Support\Collection;

class SegmentationService
{
    public const DEFAULT_SEGMENTS = [
        'blog',
        'product',
        'category',
        'collection',
        'docs',
        'paginated',
        'parameterized',
        'noindex',
        'redirect',
        'error_pages',
        'other',
    ];

    public function run(Audit $audit): array
    {
        $pages = $audit->pages()->get();
        if ($pages->isEmpty()) {
            return ['counts' => [], 'top_problems' => [], 'rules_applied' => []];
        }

        foreach ($pages as $page) {
            $segment = $this->segmentForPage($page);
            $page->segment_key = $segment;
            $page->save();
        }

        $pages = $audit->pages()->get();
        $counts = $pages->groupBy(fn (AuditPage $page) => $page->segment_key ?: 'other')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->toArray();

        $this->enrichIssueSegments($audit, $pages);

        $topProblems = $audit->issues()->get()
            ->groupBy(fn (AuditIssue $issue) => $issue->details_json['segment'] ?? 'other')
            ->map(function (Collection $segmentIssues, string $segment) {
                $top = $segmentIssues->groupBy('code')
                    ->map(fn (Collection $codeIssues) => $codeIssues->count())
                    ->sortDesc()
                    ->take(5)
                    ->toArray();

                return [
                    'segment' => $segment,
                    'issues_total' => $segmentIssues->count(),
                    'top_issue_codes' => $top,
                ];
            })
            ->sortByDesc('issues_total')
            ->values()
            ->toArray();

        return [
            'counts' => $counts,
            'top_problems' => $topProblems,
            'rules_applied' => self::DEFAULT_SEGMENTS,
        ];
    }

    protected function enrichIssueSegments(Audit $audit, Collection $pages): void
    {
        $segmentsByUrl = $pages->mapWithKeys(fn (AuditPage $page) => [$page->url => $page->segment_key ?: 'other'])->toArray();
        $issues = $audit->issues()->get();
        foreach ($issues as $issue) {
            if (empty($issue->url)) {
                continue;
            }
            $segment = $segmentsByUrl[$issue->url] ?? 'other';
            $details = is_array($issue->details_json) ? $issue->details_json : [];
            if (($details['segment'] ?? null) === $segment) {
                continue;
            }
            $details['segment'] = $segment;
            $issue->details_json = $details;
            $issue->save();
        }
    }

    protected function segmentForPage(AuditPage $page): string
    {
        $url = strtolower($page->url ?? '');
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $query = strtolower((string) parse_url($url, PHP_URL_QUERY));
        $robots = strtolower($page->robots_meta ?? '');
        $xRobots = strtolower($page->x_robots_tag ?? '');
        $status = (int) ($page->status_code ?? 0);

        if ($status >= 400) {
            return 'error_pages';
        }
        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }
        if (str_contains($robots, 'noindex') || str_contains($xRobots, 'noindex')) {
            return 'noindex';
        }
        if ($query !== '') {
            if (preg_match('/(^|&)(page|p|paged)=\d+/i', $query)) {
                return 'paginated';
            }

            return 'parameterized';
        }
        if (preg_match('#/(blog|news|article|articles)(/|$)#i', $path)) {
            return 'blog';
        }
        if (preg_match('#/(product|products|shop|item|sku)(/|$)#i', $path)) {
            return 'product';
        }
        if (preg_match('#/(category|categories|cat|taxonomy)(/|$)#i', $path)) {
            return 'category';
        }
        if (preg_match('#/(collection|collections)(/|$)#i', $path)) {
            return 'collection';
        }
        if (preg_match('#/(docs|documentation|kb|knowledge-base|help)(/|$)#i', $path)) {
            return 'docs';
        }

        return 'other';
    }
}


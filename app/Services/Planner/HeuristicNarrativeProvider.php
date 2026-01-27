<?php

namespace App\Services\Planner;

class HeuristicNarrativeProvider implements NarrativeProviderInterface
{
    /**
     * Explain why this task matters
     */
    public function explainWhy(array $item): string
    {
        $type = $item['type'] ?? '';
        $evidence = $item['evidence'] ?? [];

        switch ($type) {
            case 'fix_critical_seo':
                $count = $evidence['critical_issues'] ?? 0;
                return "Your website has {$count} critical SEO issues that are preventing search engines from properly indexing and ranking your pages. Fixing these will improve your overall SEO health score.";

            case 'improve_cwv':
                $page = $evidence['worst_page'] ?? 'your pages';
                return "Core Web Vitals are poor on {$page}, which can negatively impact search rankings and user experience. Google uses these metrics as ranking factors.";

            case 'ctr_optimization':
                $drop = $evidence['ctr_drop'] ?? 0;
                $queries = $evidence['low_ctr_queries'] ?? 0;
                return "You have {$queries} queries with high impressions but low click-through rates. Improving titles and meta descriptions could increase clicks by up to {$drop}%.";

            case 'lost_backlinks':
                $lost = $evidence['lost_links'] ?? 0;
                return "You've lost {$lost} backlinks recently. These lost links can negatively impact your domain authority and search rankings.";

            case 'meta_failed_fix':
                $failed = $evidence['failed_count'] ?? 0;
                return "{$failed} meta tag updates failed to publish. This means your SEO changes aren't going live, preventing improvements from taking effect.";

            case 'gsc_clicks_drop':
                $drop = $evidence['clicks_drop_pct'] ?? 0;
                return "Search Console clicks dropped {$drop}% compared to the previous period. This indicates declining search visibility.";

            case 'ga_sessions_drop':
                $drop = $evidence['sessions_drop_pct'] ?? 0;
                return "Analytics sessions dropped {$drop}% compared to the previous period. This suggests traffic decline that needs investigation.";

            default:
                return "This task will help improve your website's SEO performance and search visibility.";
        }
    }

    /**
     * Generate checklist steps
     */
    public function generateChecklist(array $item): array
    {
        $type = $item['type'] ?? '';
        $domainId = $item['domain_id'] ?? null;
        $relatedUrl = $item['related_url'] ?? null;

        $baseUrl = url("/domains/{$domainId}");

        switch ($type) {
            case 'fix_critical_seo':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open latest audit report',
                        'link' => "{$baseUrl}/audits",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Filter by Critical severity issues',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Fix pages with 4xx/5xx status codes first',
                        'link' => $relatedUrl,
                    ],
                    [
                        'step' => 4,
                        'text' => 'Ensure each important page has title + H1',
                        'link' => "{$baseUrl}/meta",
                    ],
                    [
                        'step' => 5,
                        'text' => 'Re-run audit to confirm score improved',
                        'link' => "{$baseUrl}/audits",
                    ],
                ];

            case 'improve_cwv':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open latest audit report',
                        'link' => "{$baseUrl}/audits",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Go to Performance tab',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Review Core Web Vitals metrics (LCP, CLS, INP)',
                        'link' => null,
                    ],
                    [
                        'step' => 4,
                        'text' => 'Optimize images and reduce JavaScript on worst pages',
                        'link' => $relatedUrl,
                    ],
                    [
                        'step' => 5,
                        'text' => 'Test improvements with PageSpeed Insights',
                        'link' => null,
                    ],
                ];

            case 'ctr_optimization':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open Google Integrations page',
                        'link' => "{$baseUrl}/integrations/google",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Review queries with high impressions + low CTR',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Pick top 5 pages and update titles/meta in Meta Editor',
                        'link' => "{$baseUrl}/meta",
                    ],
                    [
                        'step' => 4,
                        'text' => 'Make titles more compelling and include target keywords',
                        'link' => null,
                    ],
                    [
                        'step' => 5,
                        'text' => 'Wait 7-14 days then compare CTR in GSC',
                        'link' => "{$baseUrl}/integrations/google",
                    ],
                ];

            case 'lost_backlinks':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open Backlinks report (latest run)',
                        'link' => "{$baseUrl}/backlinks",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Go to New/Lost tab',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Review lost links list',
                        'link' => null,
                    ],
                    [
                        'step' => 4,
                        'text' => 'Check if lost links are due to 404/redirect issues',
                        'link' => "{$baseUrl}/audits",
                    ],
                    [
                        'step' => 5,
                        'text' => 'Recover via outreach or redirect fixes',
                        'link' => null,
                    ],
                    [
                        'step' => 6,
                        'text' => 'Re-run backlinks snapshot to track recovery',
                        'link' => "{$baseUrl}/backlinks",
                    ],
                ];

            case 'meta_failed_fix':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open Meta Editor filtered "Failed"',
                        'link' => "{$baseUrl}/meta",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Open each failed change and read error message',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Reconnect connector if auth error',
                        'link' => "{$baseUrl}/meta",
                    ],
                    [
                        'step' => 4,
                        'text' => 'Retry publish for each failed change',
                        'link' => null,
                    ],
                    [
                        'step' => 5,
                        'text' => 'Verify changes on live site (WP/Shopify)',
                        'link' => $relatedUrl,
                    ],
                ];

            case 'gsc_clicks_drop':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open Google Integrations page',
                        'link' => "{$baseUrl}/integrations/google",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Review clicks trend chart',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Check top pages and queries for changes',
                        'link' => null,
                    ],
                    [
                        'step' => 4,
                        'text' => 'Investigate position drops in top queries',
                        'link' => null,
                    ],
                    [
                        'step' => 5,
                        'text' => 'Update content or meta tags for affected pages',
                        'link' => "{$baseUrl}/meta",
                    ],
                ];

            case 'ga_sessions_drop':
                return [
                    [
                        'step' => 1,
                        'text' => 'Open Google Integrations page',
                        'link' => "{$baseUrl}/integrations/google",
                    ],
                    [
                        'step' => 2,
                        'text' => 'Review GA4 sessions trend',
                        'link' => null,
                    ],
                    [
                        'step' => 3,
                        'text' => 'Check landing pages with low engagement',
                        'link' => null,
                    ],
                    [
                        'step' => 4,
                        'text' => 'Investigate traffic sources for changes',
                        'link' => null,
                    ],
                    [
                        'step' => 5,
                        'text' => 'Improve content or fix technical issues',
                        'link' => "{$baseUrl}/audits",
                    ],
                ];

            default:
                return [
                    [
                        'step' => 1,
                        'text' => 'Review the related data in the dashboard',
                        'link' => $relatedUrl,
                    ],
                    [
                        'step' => 2,
                        'text' => 'Take action based on the evidence',
                        'link' => null,
                    ],
                ];
        }
    }
}



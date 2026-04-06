<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;

class JsRenderingDiffService
{
    public function __construct(
        protected RulesEngine $rulesEngine
    ) {}

    public function analyzePage(Audit $audit, AuditPage $page): void
    {
        $snap = $page->js_render_snapshot;
        if (!is_array($snap)) {
            return;
        }

        $nav = $snap['navigation'] ?? [];
        $rendered = $snap['rendered'] ?? null;

        if ($rendered === null && !($nav['ok'] ?? false)) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_NAVIGATION_FAILED',
                'title' => 'Headless browser could not render this page',
                'description' => 'JavaScript rendering failed or the DOM could not be read after navigation. SEO signals could not be compared for this URL.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'score_penalty' => 10,
                'diff_type' => 'navigation_failed',
                'filter_tags' => ['missing_in_rendered', 'changed_after_render'],
                'recommendation' => 'Verify the URL loads in a real browser, check for bot blocking or script errors, and ensure critical SEO tags exist in the initial HTML where possible.',
                'raw' => $snap['raw'] ?? [],
                'rendered' => ['error' => $nav['error'] ?? null, 'http_status' => $nav['http_status'] ?? null],
            ]);

            return;
        }

        if (!is_array($rendered)) {
            return;
        }

        $ratio = (float) config('seo_audit.js_render.content_divergence_ratio', 0.35);
        $pageUrl = $page->url;

        $rawTitle = trim((string) $page->title);
        $rTitle = trim((string) ($rendered['title'] ?? ''));
        if ($rawTitle === '' && $rTitle !== '') {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_TITLE_APPEARED',
                'title' => 'Page title only appears after JavaScript runs',
                'description' => 'The initial HTML has no title, but the rendered DOM has one. Crawlers that do not execute JavaScript may not see this title.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'score_penalty' => 14,
                'diff_type' => 'title_appeared_after_render',
                'filter_tags' => ['missing_in_raw', 'changed_after_render'],
                'recommendation' => 'Output a descriptive <title> in the server-rendered HTML, or use SSR/SSG so the title is present before hydration.',
                'raw' => ['title' => $rawTitle],
                'rendered' => ['title' => $rTitle],
            ]);
        }

        $rawMeta = trim((string) $page->meta_description);
        $rMeta = trim((string) ($rendered['meta_description'] ?? ''));
        if ($rawMeta === '' && $rMeta !== '') {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_META_APPEARED',
                'title' => 'Meta description only appears after JavaScript runs',
                'description' => 'The meta description is injected client-side. Non-JS crawlers and many bots may miss it.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'score_penalty' => 12,
                'diff_type' => 'meta_description_appeared_after_render',
                'filter_tags' => ['missing_in_raw', 'changed_after_render'],
                'recommendation' => 'Include `<meta name="description">` in the initial HTML or prerender the head for bots.',
                'raw' => ['meta_description' => $rawMeta],
                'rendered' => ['meta_description' => $rMeta],
            ]);
        }

        $rawCanon = $this->canonicalComparable($page->canonical_url, $pageUrl);
        $rCanon = $this->canonicalComparable($rendered['canonical_url'] ?? null, $pageUrl);
        if ($rawCanon !== $rCanon) {
            $tags = ['changed_after_render', 'indexability_mismatch'];
            if ($rawCanon === null && $rCanon !== null) {
                $tags[] = 'missing_in_raw';
            }
            if ($rawCanon !== null && $rCanon === null) {
                $tags[] = 'missing_in_rendered';
            }
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_CANONICAL_DIVERGENCE',
                'title' => 'Canonical URL differs between raw HTML and rendered DOM',
                'description' => 'The canonical tag changes (or appears only) after JavaScript execution, which can confuse consolidation signals.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'score_penalty' => 14,
                'diff_type' => 'canonical_changed_after_render',
                'filter_tags' => array_values(array_unique($tags)),
                'recommendation' => 'Stabilize the canonical: emit the same canonical in HTML and after render, preferring server-rendered `<link rel="canonical">`.',
                'raw' => ['canonical_url' => $page->canonical_url],
                'rendered' => ['canonical_url' => $rendered['canonical_url'] ?? null],
            ]);
        }

        $rawRobots = $this->normRobots($page->robots_meta);
        $rRobots = $this->normRobots($rendered['robots_meta'] ?? '');
        if ($rawRobots !== $rRobots) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_ROBOTS_META_CHANGED',
                'title' => 'Robots meta directives differ after render',
                'description' => 'The `meta name="robots"` content is not the same in the initial HTML and the rendered DOM.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'score_penalty' => 10,
                'diff_type' => 'robots_directives_changed',
                'filter_tags' => ['changed_after_render', 'indexability_mismatch'],
                'recommendation' => 'Serve consistent robots meta in the first HTML response. Avoid client-only changes to indexing directives.',
                'raw' => ['robots_meta' => $page->robots_meta],
                'rendered' => ['robots_meta' => $rendered['robots_meta'] ?? ''],
            ]);
        }

        $rawIdx = $this->rawIndexability($page);
        $rIdx = [
            'noindex' => (bool) ($rendered['indexability']['noindex'] ?? false),
            'nofollow' => (bool) ($rendered['indexability']['nofollow'] ?? false),
        ];
        if ($rawIdx['noindex'] !== $rIdx['noindex']) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_INDEXABILITY_MISMATCH',
                'title' => 'Indexability (noindex) differs after JavaScript',
                'description' => 'Initial HTML and rendered DOM disagree on noindex, using meta robots and X-Robots-Tag in the raw response.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'score_penalty' => 15,
                'diff_type' => 'indexability_noindex_mismatch',
                'filter_tags' => ['indexability_mismatch', 'changed_after_render'],
                'recommendation' => 'Align noindex between HTTP headers, raw HTML, and client-rendered content so all crawlers see the same intent.',
                'raw' => [
                    'indexability' => $rawIdx,
                    'robots_meta' => $page->robots_meta,
                    'x_robots_tag' => $page->x_robots_tag,
                ],
                'rendered' => [
                    'indexability' => $rIdx,
                    'robots_meta' => $rendered['robots_meta'] ?? '',
                    'x_robots_tag' => $rendered['x_robots_tag'] ?? null,
                ],
            ]);
        }

        $rawVis = (int) ($page->visible_text_length ?? 0);
        $rVis = (int) ($rendered['visible_text_length'] ?? 0);
        $maxLen = max($rawVis, $rVis, 1);
        if (abs($rawVis - $rVis) / $maxLen >= $ratio && $maxLen >= 80) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_CONTENT_DIVERGENCE',
                'title' => 'Visible text length differs strongly after render',
                'description' => 'Body text available to users after JavaScript differs substantially from the raw HTML text, which may affect how crawlers evaluate content.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'score_penalty' => 9,
                'diff_type' => 'content_divergence',
                'filter_tags' => ['changed_after_render'],
                'recommendation' => 'Improve SSR/hydration so meaningful content exists in HTML, or use dynamic rendering for verified bots if you rely on client-only content.',
                'raw' => ['visible_text_length' => $rawVis, 'word_count' => $page->word_count],
                'rendered' => ['visible_text_length' => $rVis, 'word_count' => $rendered['word_count'] ?? 0],
            ]);
        }

        $rawInt = (int) ($page->internal_links_count ?? 0);
        $rInt = (int) ($rendered['internal_links_count'] ?? 0);
        if ($rawInt === 0 && $rInt > 0) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_INTERNAL_LINKS_AFTER_RENDER',
                'title' => 'Internal links only appear after JavaScript',
                'description' => 'No internal links were found in the raw HTML, but the rendered DOM contains internal links.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'score_penalty' => 11,
                'diff_type' => 'internal_links_after_render',
                'filter_tags' => ['missing_in_raw', 'changed_after_render'],
                'recommendation' => 'Expose key internal links in the initial HTML or use progressive enhancement so crawlers can discover important URLs.',
                'raw' => ['internal_links_count' => $rawInt],
                'rendered' => ['internal_links_count' => $rInt],
            ]);
        }

        $rWords = (int) ($rendered['word_count'] ?? 0);
        if (($rWords < 50 || $rVis < 200) && (($page->word_count ?? 0) >= 80 || $rawVis >= 300)) {
            $this->persistIssue($audit, $page, [
                'code' => 'JS_RENDER_THIN_AFTER_RENDER',
                'title' => 'Rendered content is thin compared to raw HTML',
                'description' => 'After JavaScript, visible content is very small even though the raw HTML suggested more text (possible empty shell or failed hydration).',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'score_penalty' => 10,
                'diff_type' => 'rendered_thin_or_empty',
                'filter_tags' => ['missing_in_rendered', 'changed_after_render'],
                'recommendation' => 'Fix client errors, ensure APIs load, and verify the app hydrates. Provide baseline readable content in HTML.',
                'raw' => ['visible_text_length' => $rawVis, 'word_count' => $page->word_count],
                'rendered' => ['visible_text_length' => $rVis, 'word_count' => $rWords],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    protected function persistIssue(Audit $audit, AuditPage $page, array $spec): void
    {
        $impact = $spec['impact'] ?? AuditIssue::IMPACT_LOW;
        $severity = match ($impact) {
            AuditIssue::IMPACT_HIGH => AuditIssue::SEVERITY_CRITICAL,
            AuditIssue::IMPACT_MEDIUM => AuditIssue::SEVERITY_WARNING,
            default => AuditIssue::SEVERITY_INFO,
        };

        $rawPayload = $spec['raw'] ?? [];
        $renderedPayload = $spec['rendered'] ?? [];

        $this->rulesEngine->createCustomIssue($audit, [
            'code' => $spec['code'],
            'category' => 'technical',
            'module_key' => 'js_rendering',
            'title' => $spec['title'],
            'description' => $spec['description'],
            'impact' => $impact,
            'effort' => AuditIssue::EFFORT_MEDIUM,
            'score_penalty' => (int) ($spec['score_penalty'] ?? 0),
            'affected_count' => 1,
            'sample_urls' => [$page->url],
            'url' => $page->url,
            'recommendation' => $spec['recommendation'],
            'details_json' => [
                'diff_type' => $spec['diff_type'],
                'filter_tags' => $spec['filter_tags'] ?? [],
                'severity' => $severity,
                'raw' => $rawPayload,
                'rendered' => $renderedPayload,
            ],
        ]);
    }

    /**
     * @return array{noindex: bool, nofollow: bool}
     */
    protected function rawIndexability(AuditPage $page): array
    {
        $rm = strtolower($page->robots_meta ?? '');
        $xr = strtolower((string) ($page->x_robots_tag ?? ''));

        return [
            'noindex' => str_contains($rm, 'noindex') || str_contains($xr, 'noindex'),
            'nofollow' => str_contains($rm, 'nofollow') || str_contains($xr, 'nofollow'),
        ];
    }

    protected function normRobots(?string $value): string
    {
        $v = strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));

        return $v;
    }

    protected function canonicalComparable(?string $href, string $pageUrl): ?string
    {
        if ($href === null || trim($href) === '') {
            return null;
        }

        $absolute = $this->resolveHref(trim($href), $pageUrl);
        $parts = parse_url($absolute);
        if (empty($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./i', '', $parts['host']));
        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $host . $path . $query;
    }

    protected function resolveHref(string $href, string $pageUrl): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $base = parse_url($pageUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        $path = $base['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '.' || $dir === '\\') {
            $dir = '/';
        }
        $prefix = rtrim($scheme . '://' . $host . ($dir === '/' ? '/' : $dir . '/'), '/');

        return $prefix . '/' . ltrim($href, '/');
    }
}

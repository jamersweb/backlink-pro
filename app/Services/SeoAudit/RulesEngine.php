<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Models\AuditIssue;
use App\Models\AuditLink;
use App\Models\AuditAsset;

/**
 * SEO Audit Rules Engine
 * 
 * Evaluates SEO rules and generates issues with scoring.
 * Rules are defined in a config-like array for easy expansion.
 */
class RulesEngine
{
    /**
     * Rule definitions
     * Each rule has: code, title, description, impact, effort, penalty, check callback
     */
    protected array $rules = [];

    public function __construct()
    {
        $this->initializeRules();
    }

    /**
     * Initialize rule definitions
     * 
     * Rules are organized by category for easier management.
     * Each rule should have a unique code, clear impact/effort, and consistent penalties.
     */
    protected function initializeRules(): void
    {
        $this->rules = [
            // On-Page SEO Rules
            [
                'code' => 'ONPAGE_TITLE_MISSING',
                'title' => 'Missing Page Title',
                'description' => 'The page does not have a <title> tag. This is critical for SEO.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 15,
                'category' => 'onpage',
                'check' => fn($page) => empty($page->title),
            ],
            [
                'code' => 'ONPAGE_TITLE_TOO_SHORT',
                'title' => 'Title Too Short',
                'description' => 'The page title is less than 30 characters. Titles should be 30-60 characters for optimal SEO.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 5,
                'category' => 'onpage',
                'check' => fn($page) => $page->title_len && $page->title_len < 30,
            ],
            [
                'code' => 'ONPAGE_TITLE_TOO_LONG',
                'title' => 'Title Too Long',
                'description' => 'The page title exceeds 60 characters. Search engines may truncate it.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 3,
                'category' => 'onpage',
                'check' => fn($page) => $page->title_len && $page->title_len > 60,
            ],
            [
                'code' => 'ONPAGE_META_DESCRIPTION_MISSING',
                'title' => 'Missing Meta Description',
                'description' => 'The page does not have a meta description tag. This affects click-through rates from search results.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 10,
                'category' => 'onpage',
                'check' => fn($page) => empty($page->meta_description),
            ],
            [
                'code' => 'ONPAGE_META_DESCRIPTION_TOO_SHORT',
                'title' => 'Meta Description Too Short',
                'description' => 'The meta description is less than 70 characters. Aim for 70-160 characters.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 5,
                'category' => 'onpage',
                'check' => fn($page) => $page->meta_len && $page->meta_len < 70,
            ],
            [
                'code' => 'ONPAGE_META_DESCRIPTION_TOO_LONG',
                'title' => 'Meta Description Too Long',
                'description' => 'The meta description exceeds 160 characters. Search engines may truncate it.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 3,
                'category' => 'onpage',
                'check' => fn($page) => $page->meta_len && $page->meta_len > 160,
            ],
            [
                'code' => 'ONPAGE_H1_MISSING',
                'title' => 'Missing H1 Tag',
                'description' => 'The page does not have an H1 heading. H1 tags help search engines understand page structure.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 10,
                'category' => 'onpage',
                'check' => fn($page) => $page->h1_count === 0,
            ],
            [
                'code' => 'ONPAGE_MULTIPLE_H1',
                'title' => 'Multiple H1 Tags',
                'description' => 'The page has more than one H1 tag. Best practice is to use a single H1 per page.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 5,
                'category' => 'onpage',
                'check' => fn($page) => $page->h1_count > 1,
            ],
            [
                'code' => 'ONPAGE_IMAGES_MISSING_ALT',
                'title' => 'Images Missing Alt Text',
                'description' => 'Some images on the page are missing alt text. Alt text improves accessibility and SEO.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'penalty' => 8,
                'category' => 'onpage',
                'check' => fn($page) => $page->images_total > 0 && $page->images_missing_alt > 0,
            ],
            [
                'code' => 'ONPAGE_LOW_WORD_COUNT',
                'title' => 'Low Word Count',
                'description' => 'The page has less than 300 words. Thin content may not rank well in search engines.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_HARD,
                'penalty' => 10,
                'category' => 'onpage',
                'check' => fn($page) => $page->word_count > 0 && $page->word_count < 300,
            ],
            [
                'code' => 'ONPAGE_LANG_MISSING',
                'title' => 'Missing HTML Lang Attribute',
                'description' => 'The page is missing the HTML lang attribute. This helps search engines and accessibility tools understand language.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 4,
                'category' => 'onpage',
                'check' => fn($page) => empty($page->lang),
            ],
            [
                'code' => 'TECHNICAL_NOINDEX_DETECTED',
                'title' => 'Noindex Detected',
                'description' => 'The page is marked as noindex via meta robots or X-Robots-Tag. This prevents indexing.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 15,
                'category' => 'technical',
                'check' => function ($page) {
                    $meta = strtolower($page->robots_meta ?? '');
                    $header = strtolower($page->x_robots_tag ?? '');
                    return str_contains($meta, 'noindex') || str_contains($header, 'noindex');
                },
            ],
            [
                'code' => 'USABILITY_VIEWPORT_MISSING',
                'title' => 'Missing Viewport Meta Tag',
                'description' => 'The page does not include a viewport meta tag, which can harm mobile rendering.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 8,
                'category' => 'usability',
                'check' => fn($page) => !$page->viewport_present,
            ],
            [
                'code' => 'USABILITY_FAVICON_MISSING',
                'title' => 'Missing Favicon',
                'description' => 'The page does not declare a favicon. Favicons improve brand visibility and UX.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 3,
                'category' => 'usability',
                'check' => fn($page) => !$page->favicon_present,
            ],
            
            // Technical SEO Rules
            [
                'code' => 'TECHNICAL_CANONICAL_MISSING',
                'title' => 'Missing Canonical URL',
                'description' => 'The page does not have a canonical URL tag. This helps prevent duplicate content issues.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 5,
                'category' => 'technical',
                'check' => fn($page) => empty($page->canonical_url),
            ],
            [
                'code' => 'TECHNICAL_HTTPS_NOT_ENFORCED',
                'title' => 'HTTPS Not Enforced',
                'description' => 'The page is served over HTTP instead of HTTPS. HTTPS is required for security and SEO.',
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'penalty' => 15,
                'category' => 'technical',
                'check' => fn($page) => str_starts_with($page->url, 'http://'),
            ],
            
            // Social Media Rules
            [
                'code' => 'SOCIAL_OG_TAGS_MISSING',
                'title' => 'Missing Open Graph Tags',
                'description' => 'The page does not have Open Graph meta tags. These improve how your page appears when shared on social media.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 5,
                'category' => 'social',
                'check' => fn($page) => !$page->og_present,
            ],
            [
                'code' => 'SOCIAL_TWITTER_CARDS_MISSING',
                'title' => 'Missing Twitter Card Tags',
                'description' => 'The page does not have Twitter Card meta tags. These improve how your page appears when shared on Twitter.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'penalty' => 3,
                'category' => 'social',
                'check' => fn($page) => !$page->twitter_cards_present,
            ],
            
            // Performance Rules
            [
                'code' => 'PERFORMANCE_LARGE_HTML_SIZE',
                'title' => 'Large HTML Size',
                'description' => 'The HTML page size exceeds 500KB. Large pages load slower and may affect user experience.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_HARD,
                'penalty' => 8,
                'category' => 'performance',
                'check' => fn($page) => $page->html_size_bytes && $page->html_size_bytes > 500 * 1024,
            ],
            
            // Links Rules
            [
                'code' => 'LINKS_TOO_MANY',
                'title' => 'Too Many Links',
                'description' => 'The page has more than 300 links. Too many links can dilute link equity and confuse users.',
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'penalty' => 5,
                'category' => 'links',
                'check' => fn($page) => ($page->internal_links_count + $page->external_links_count) > 300,
            ],
        ];
    }

    /**
     * Evaluate all rules and create issues (Phase 1 - single page)
     */
    public function evaluate(Audit $audit, AuditPage $page): array
    {
        $issues = [];
        $categoryPenalties = [
            'onpage' => 0,
            'technical' => 0,
            'performance' => 0,
            'links' => 0,
            'social' => 0,
            'usability' => 0,
            'local' => 0,
            'security' => 0,
        ];

        foreach ($this->rules as $rule) {
            if ($rule['check']($page)) {
                $issue = $this->createIssue($audit, $rule, $page);
                $issues[] = $issue;
                
                // Accumulate penalties by category
                $category = $rule['category'] ?? 'onpage';
                if (isset($categoryPenalties[$category])) {
                    $categoryPenalties[$category] += $rule['penalty'];
                }
            }
        }

        return [
            'issues' => $issues,
            'categoryPenalties' => $categoryPenalties,
        ];
    }

    /**
     * Evaluate Phase 2 rules across all pages and links (collection-based)
     */
    public function evaluateCollection(Audit $audit): array
    {
        $issues = [];
        $categoryPenalties = [
            'onpage' => 0,
            'technical' => 0,
            'performance' => 0,
            'links' => 0,
            'social' => 0,
            'usability' => 0,
            'local' => 0,
            'security' => 0,
        ];

        $pages = $audit->pages;
        $links = $audit->links;

        // Evaluate per-page rules
        foreach ($pages as $page) {
            $pageEvaluation = $this->evaluate($audit, $page);
            $issues = array_merge($issues, $pageEvaluation['issues']);
            foreach ($pageEvaluation['categoryPenalties'] as $category => $penalty) {
                $categoryPenalties[$category] += $penalty;
            }
        }

        // Phase 2: Broken Internal Links
        $brokenInternalLinks = $links->where('type', AuditLink::TYPE_INTERNAL)
            ->where('is_broken', true);
        
        if ($brokenInternalLinks->count() > 0) {
            $topBroken = $brokenInternalLinks->take(20)->map(function ($link) {
                return $link->from_url . ' → ' . $link->to_url;
            })->toArray();

            $issue = $this->createIssueRecord([
                'audit_id' => $audit->id,
                'code' => 'TECH_BROKEN_INTERNAL_LINKS',
                'category' => 'technical',
                'title' => 'Broken Internal Links',
                'description' => "Found {$brokenInternalLinks->count()} broken internal links. Broken links hurt user experience and SEO.",
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'score_penalty' => min(20, $brokenInternalLinks->count() * 2),
                'affected_count' => $brokenInternalLinks->count(),
                'sample_urls' => $topBroken,
                'recommendation' => 'Fix or remove broken internal links. Update URLs that have changed or return 404 errors.',
                'fix_steps' => [
                    'steps' => [
                        'Identify all broken links from the Links tab',
                        'Update URLs to correct destinations',
                        'Remove links to pages that no longer exist',
                        'Set up 301 redirects if pages have moved',
                    ],
                    'snippet' => null,
                ],
            ]);
            $issues[] = $issue;
            $categoryPenalties['technical'] += $issue->score_penalty;
        }

        // Phase 2: Redirect Chains
        $redirectChains = $links->where('redirect_hops', '>=', 2);
        
        if ($redirectChains->count() > 0) {
            $redirectSamples = $redirectChains->take(10)->map(function ($link) {
                return $link->from_url . ' -> ' . $link->to_url;
            })->toArray();
            $issue = $this->createIssueRecord([
                'audit_id' => $audit->id,
                'code' => 'TECH_REDIRECT_CHAINS',
                'category' => 'technical',
                'title' => 'Redirect Chains Detected',
                'description' => "Found {$redirectChains->count()} links with redirect chains (2+ hops). Redirect chains slow down page loads and waste crawl budget.",
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'score_penalty' => min(15, $redirectChains->count()),
                'affected_count' => $redirectChains->count(),
                'sample_urls' => $redirectSamples,
                'recommendation' => 'Update internal links to point directly to final URLs. Consolidate redirect chains into single redirects.',
                'fix_steps' => [
                    'steps' => [
                        'Identify redirect chains from the Links tab',
                        'Update internal links to point to final URLs',
                        'Consolidate multiple redirects into single redirects',
                    ],
                ],
            ]);
            $issues[] = $issue;
            $categoryPenalties['technical'] += $issue->score_penalty;
        }

        // Phase 2: Duplicate Titles
        $duplicateTitles = $pages->whereNotNull('title')
            ->groupBy('title')
            ->filter(fn($group) => $group->count() > 1);
        
        if ($duplicateTitles->count() > 0) {
            $duplicateGroups = [];
            foreach ($duplicateTitles as $title => $group) {
                $duplicateGroups[] = [
                    'title' => $title,
                    'urls' => $group->pluck('url')->toArray(),
                    'count' => $group->count(),
                ];
            }
            $sampleUrls = collect($duplicateGroups)
                ->flatMap(fn($group) => $group['urls'])
                ->take(10)
                ->values()
                ->toArray();

            $issue = $this->createIssueRecord([
                'audit_id' => $audit->id,
                'code' => 'ONPAGE_DUPLICATE_TITLES',
                'category' => 'onpage',
                'title' => 'Duplicate Page Titles',
                'description' => "Found {$duplicateTitles->count()} groups of pages with duplicate titles. Each page should have a unique title.",
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'score_penalty' => min(15, $duplicateTitles->count() * 3),
                'affected_count' => $duplicateTitles->sum(fn($group) => $group->count()),
                'sample_urls' => $sampleUrls,
                'recommendation' => 'Make each page title unique and descriptive. Include page-specific keywords.',
                'fix_steps' => [
                    'steps' => [
                        'Review duplicate title groups',
                        'Create unique, descriptive titles for each page',
                        'Include page-specific keywords',
                    ],
                ],
            ]);
            $issues[] = $issue;
            $categoryPenalties['onpage'] += $issue->score_penalty;
        }

        // Phase 2: Duplicate Meta Descriptions
        $duplicateMeta = $pages->whereNotNull('meta_description')
            ->groupBy('meta_description')
            ->filter(fn($group) => $group->count() > 1);
        
        if ($duplicateMeta->count() > 0) {
            $metaSampleUrls = $duplicateMeta
                ->flatMap(fn($group) => $group->pluck('url'))
                ->take(10)
                ->values()
                ->toArray();
            $issue = $this->createIssueRecord([
                'audit_id' => $audit->id,
                'code' => 'ONPAGE_DUPLICATE_META',
                'category' => 'onpage',
                'title' => 'Duplicate Meta Descriptions',
                'description' => "Found {$duplicateMeta->count()} groups of pages with duplicate meta descriptions. Each page should have a unique meta description.",
                'impact' => AuditIssue::IMPACT_LOW,
                'effort' => AuditIssue::EFFORT_EASY,
                'score_penalty' => min(10, $duplicateMeta->count() * 2),
                'affected_count' => $duplicateMeta->sum(fn($group) => $group->count()),
                'sample_urls' => $metaSampleUrls,
                'recommendation' => 'Create unique meta descriptions for each page that accurately describe the page content.',
                'fix_steps' => [
                    'steps' => [
                        'Review duplicate meta description groups',
                        'Write unique, compelling meta descriptions',
                        'Include call-to-action when appropriate',
                    ],
                ],
            ]);
            $issues[] = $issue;
            $categoryPenalties['onpage'] += $issue->score_penalty;
        }

        // Phase 2: Thin Pages
        $thinPages = $pages->filter(fn($page) => $page->word_count > 0 && $page->word_count < 300);
        
        if ($thinPages->count() > 0) {
            $thinSampleUrls = $thinPages->take(10)->pluck('url')->toArray();
            $issue = $this->createIssueRecord([
                'audit_id' => $audit->id,
                'code' => 'CONTENT_THIN_PAGES',
                'category' => 'onpage',
                'title' => 'Thin Content Pages',
                'description' => "Found {$thinPages->count()} pages with less than 300 words. Thin content may not rank well in search engines.",
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_HARD,
                'score_penalty' => min(20, $thinPages->count() * 2),
                'affected_count' => $thinPages->count(),
                'sample_urls' => $thinSampleUrls,
                'recommendation' => 'Add more valuable, unique content to thin pages. Aim for at least 300 words of quality content per page.',
                'fix_steps' => [
                    'steps' => [
                        'Identify thin content pages',
                        'Add more detailed, valuable content',
                        'Include relevant keywords naturally',
                        'Add images, videos, or other media',
                    ],
                ],
            ]);
            $issues[] = $issue;
            $categoryPenalties['onpage'] += $issue->score_penalty;
        }

        // Phase 2: Orphan Pages (from sitemap but not discovered by crawl)
        $sitemapUrls = \App\Models\AuditUrlQueue::where('audit_id', $audit->id)
            ->where('discovered_from', 'sitemap')
            ->pluck('url_normalized')
            ->toArray();
        
        if (count($sitemapUrls) > 0) {
            $crawledUrls = $pages->pluck('url')->toArray();
            $orphanUrls = array_diff($sitemapUrls, $crawledUrls);
            
            if (count($orphanUrls) > 0) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'TECH_ORPHAN_PAGES',
                    'category' => 'technical',
                    'title' => 'Orphan Pages Detected',
                    'description' => "Found " . count($orphanUrls) . " pages in sitemap that weren't discovered during crawl. These pages may lack internal links.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => min(15, count($orphanUrls) * 2),
                    'affected_count' => count($orphanUrls),
                    'sample_urls' => array_slice(array_values($orphanUrls), 0, 10),
                    'recommendation' => 'Add internal links to orphan pages from your main navigation or content pages. Ensure all important pages are accessible via internal links.',
                    'fix_steps' => [
                        'steps' => [
                            'Review orphan pages from sitemap',
                            'Add internal links from main navigation',
                            'Link from relevant content pages',
                            'Ensure all important pages are discoverable',
                        ],
                        'snippet' => null,
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['technical'] += $issue->score_penalty;
            }
        }

        return [
            'issues' => $issues,
            'categoryPenalties' => $categoryPenalties,
        ];
    }

    /**
     * Evaluate Phase 3 rules (Performance + Security)
     */
    public function evaluatePhase3(Audit $audit): array
    {
        $issues = [];
        $categoryPenalties = [
            'performance' => 0,
            'security' => 0,
        ];

        $pages = $audit->pages()->whereNotNull('performance_metrics')->get();

        // Performance Rules
        foreach ($pages as $page) {
            $metrics = $page->performance_metrics ?? [];
            
            // PERF_LCP_HIGH
            if (isset($metrics['mobile']['lcp']) && $metrics['mobile']['lcp'] > 4000) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'PERF_LCP_HIGH',
                    'category' => 'performance',
                    'title' => 'High Largest Contentful Paint (Mobile)',
                    'description' => "LCP on {$page->url} is {$metrics['mobile']['lcp']}ms (target: <4000ms). This affects user experience and Core Web Vitals.",
                    'impact' => AuditIssue::IMPACT_HIGH,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 15,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Optimize LCP by reducing server response times, optimizing images, and eliminating render-blocking resources.',
                    'fix_steps' => [
                        'steps' => [
                            'Optimize server response times',
                            'Use a CDN for static assets',
                            'Optimize images (WebP, compression)',
                            'Preload critical resources',
                            'Eliminate render-blocking CSS/JS',
                        ],
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['performance'] += $issue->score_penalty;
            }

            if (isset($metrics['desktop']['lcp']) && $metrics['desktop']['lcp'] > 2500) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'PERF_LCP_HIGH_DESKTOP',
                    'category' => 'performance',
                    'title' => 'High Largest Contentful Paint (Desktop)',
                    'description' => "LCP on {$page->url} is {$metrics['desktop']['lcp']}ms (target: <2500ms).",
                    'impact' => AuditIssue::IMPACT_HIGH,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 10,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Optimize LCP by reducing server response times and optimizing critical resources.',
                    'fix_steps' => [
                        'steps' => [
                            'Optimize server response times',
                            'Optimize images',
                            'Preload critical resources',
                        ],
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['performance'] += $issue->score_penalty;
            }

            // PERF_TBT_HIGH
            if (isset($metrics['mobile']['tbt']) && $metrics['mobile']['tbt'] > 600) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'PERF_TBT_HIGH',
                    'category' => 'performance',
                    'title' => 'High Total Blocking Time',
                    'description' => "TBT on {$page->url} is {$metrics['mobile']['tbt']}ms (target: <600ms). This affects interactivity.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 10,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Reduce JavaScript execution time and eliminate long tasks.',
                    'fix_steps' => [
                        'steps' => [
                            'Reduce unused JavaScript',
                            'Code split large bundles',
                            'Defer non-critical JavaScript',
                            'Minify and compress JavaScript',
                        ],
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['performance'] += $issue->score_penalty;
            }

            // PERF_PAGE_WEIGHT_HIGH
            $totalAssets = $page->assets()->sum('size_bytes');
            if ($totalAssets > 5 * 1024 * 1024) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'PERF_PAGE_WEIGHT_HIGH',
                    'category' => 'performance',
                    'title' => 'Large Page Weight',
                    'description' => "Page {$page->url} has total assets of " . round($totalAssets / 1024 / 1024, 2) . "MB (target: <5MB).",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 10,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Optimize assets by compressing images, minifying CSS/JS, and removing unused resources.',
                    'fix_steps' => [
                        'steps' => [
                            'Compress images (WebP format)',
                            'Minify CSS and JavaScript',
                            'Remove unused CSS/JS',
                            'Enable Gzip/Brotli compression',
                        ],
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['performance'] += $issue->score_penalty;
            }

            // PERF_LARGE_IMAGES
            $largeImages = $page->assets()
                ->where('type', AuditAsset::TYPE_IMG)
                ->where('size_bytes', '>', 200 * 1024)
                ->count();
            
            if ($largeImages > 0) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'PERF_LARGE_IMAGES',
                    'category' => 'performance',
                    'title' => 'Large Image Files',
                    'description' => "Found {$largeImages} images larger than 200KB on {$page->url}. Large images slow down page load.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_EASY,
                    'score_penalty' => min(15, $largeImages * 2),
                    'affected_count' => $largeImages,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Optimize images by converting to WebP format, compressing, and using appropriate sizes.',
                    'fix_steps' => [
                        'steps' => [
                            'Convert images to WebP format',
                            'Compress images (reduce quality if needed)',
                            'Use responsive images with srcset',
                            'Lazy load images below the fold',
                        ],
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['performance'] += $issue->score_penalty;
            }

            // PERF_UNUSED_JS_CSS (from Lighthouse opportunities)
            $lighthouseData = $page->lighthouse_mobile ?? [];
            if (isset($lighthouseData['opportunities']) && is_array($lighthouseData['opportunities'])) {
                $unusedJs = collect($lighthouseData['opportunities'])->firstWhere('id', 'unused-javascript');
                $unusedCss = collect($lighthouseData['opportunities'])->firstWhere('id', 'unused-css-rules');
                $renderBlocking = collect($lighthouseData['opportunities'])->firstWhere('id', 'render-blocking-resources');

                if ($unusedJs && $unusedJs['score'] < 1) {
                    $savings = $unusedJs['savings'] ?? 0;
                    $savingsType = $unusedJs['savingsType'] ?? 'ms';
                    $savingsText = $savings . ($savingsType === 'ms' ? 'ms' : ' bytes');
                    
                    $issue = $this->createIssueRecord([
                        'audit_id' => $audit->id,
                        'code' => 'PERF_UNUSED_JAVASCRIPT',
                        'category' => 'performance',
                        'title' => 'Unused JavaScript',
                        'description' => "Page {$page->url} has unused JavaScript that can be removed. Potential savings: {$savingsText}.",
                        'impact' => AuditIssue::IMPACT_MEDIUM,
                        'effort' => AuditIssue::EFFORT_MEDIUM,
                        'score_penalty' => 10,
                        'affected_count' => 1,
                        'sample_urls' => [$page->url],
                        'recommendation' => $unusedJs['description'] ?? 'Remove unused JavaScript to improve page load time.',
                        'fix_steps' => [
                            'steps' => [
                                'Identify unused JavaScript files',
                                'Remove or defer non-critical scripts',
                                'Code split large bundles',
                                'Use dynamic imports for conditional code',
                            ],
                        ],
                    ]);
                    $issues[] = $issue;
                    $categoryPenalties['performance'] += $issue->score_penalty;
                }

                if ($unusedCss && $unusedCss['score'] < 1) {
                    $savings = $unusedCss['savings'] ?? 0;
                    $savingsType = $unusedCss['savingsType'] ?? 'ms';
                    $savingsText = $savings . ($savingsType === 'ms' ? 'ms' : ' bytes');
                    
                    $issue = $this->createIssueRecord([
                        'audit_id' => $audit->id,
                        'code' => 'PERF_UNUSED_CSS',
                        'category' => 'performance',
                        'title' => 'Unused CSS',
                        'description' => "Page {$page->url} has unused CSS rules. Potential savings: {$savingsText}.",
                        'impact' => AuditIssue::IMPACT_MEDIUM,
                        'effort' => AuditIssue::EFFORT_MEDIUM,
                        'score_penalty' => 8,
                        'affected_count' => 1,
                        'sample_urls' => [$page->url],
                        'recommendation' => $unusedCss['description'] ?? 'Remove unused CSS to reduce page size.',
                        'fix_steps' => [
                            'steps' => [
                                'Use tools like PurgeCSS to remove unused CSS',
                                'Split CSS into critical and non-critical',
                                'Load non-critical CSS asynchronously',
                                'Remove CSS from frameworks that you don\'t use',
                            ],
                        ],
                    ]);
                    $issues[] = $issue;
                    $categoryPenalties['performance'] += $issue->score_penalty;
                }

                if ($renderBlocking && $renderBlocking['score'] < 1) {
                    $savings = $renderBlocking['savings'] ?? 0;
                    $savingsType = $renderBlocking['savingsType'] ?? 'ms';
                    $savingsText = $savings . ($savingsType === 'ms' ? 'ms' : ' bytes');
                    
                    $issue = $this->createIssueRecord([
                        'audit_id' => $audit->id,
                        'code' => 'PERF_RENDER_BLOCKING',
                        'category' => 'performance',
                        'title' => 'Render-Blocking Resources',
                        'description' => "Page {$page->url} has render-blocking resources. Potential savings: {$savingsText}.",
                        'impact' => AuditIssue::IMPACT_HIGH,
                        'effort' => AuditIssue::EFFORT_MEDIUM,
                        'score_penalty' => 12,
                        'affected_count' => 1,
                        'sample_urls' => [$page->url],
                        'recommendation' => $renderBlocking['description'] ?? 'Eliminate render-blocking resources to improve First Contentful Paint.',
                        'fix_steps' => [
                            'steps' => [
                                'Inline critical CSS',
                                'Defer non-critical CSS',
                                'Defer or async non-critical JavaScript',
                                'Preload critical resources',
                            ],
                        ],
                    ]);
                    $issues[] = $issue;
                    $categoryPenalties['performance'] += $issue->score_penalty;
                }
            }
        }

        // Security Rules
        $pagesWithHeaders = $audit->pages()->whereNotNull('security_headers')->get();
        
        foreach ($pagesWithHeaders as $page) {
            $headers = $page->security_headers ?? [];

            // SEC_MISSING_HSTS
            if (!($headers['hsts'] ?? false)) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'SEC_MISSING_HSTS',
                    'category' => 'security',
                    'title' => 'Missing HSTS Header',
                    'description' => "Page {$page->url} is missing the Strict-Transport-Security header. This helps prevent man-in-the-middle attacks.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_EASY,
                    'score_penalty' => 10,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Add HSTS header to force HTTPS connections and improve security.',
                    'fix_steps' => [
                        'steps' => [
                            'Add HSTS header to server configuration',
                            'Set max-age to at least 31536000 (1 year)',
                            'Include includeSubDomains if applicable',
                        ],
                        'snippet' => 'Strict-Transport-Security: max-age=31536000; includeSubDomains',
                        'nginx_hint' => 'add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;',
                        'apache_hint' => 'Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"',
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['security'] += $issue->score_penalty;
            }

            // SEC_MISSING_XFO
            if (!($headers['x_frame_options'] ?? false)) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'SEC_MISSING_XFO',
                    'category' => 'security',
                    'title' => 'Missing X-Frame-Options Header',
                    'description' => "Page {$page->url} is missing the X-Frame-Options header. This helps prevent clickjacking attacks.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_EASY,
                    'score_penalty' => 8,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Add X-Frame-Options header to prevent your site from being embedded in frames.',
                    'fix_steps' => [
                        'steps' => [
                            'Add X-Frame-Options header',
                            'Set value to DENY or SAMEORIGIN',
                        ],
                        'snippet' => 'X-Frame-Options: DENY',
                        'nginx_hint' => 'add_header X-Frame-Options "DENY" always;',
                        'apache_hint' => 'Header always set X-Frame-Options "DENY"',
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['security'] += $issue->score_penalty;
            }

            // SEC_MISSING_XCTO
            if (!($headers['x_content_type_options'] ?? false)) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'SEC_MISSING_XCTO',
                    'category' => 'security',
                    'title' => 'Missing X-Content-Type-Options Header',
                    'description' => "Page {$page->url} is missing the X-Content-Type-Options header. This prevents MIME type sniffing.",
                    'impact' => AuditIssue::IMPACT_LOW,
                    'effort' => AuditIssue::EFFORT_EASY,
                    'score_penalty' => 5,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Add X-Content-Type-Options header to prevent browsers from MIME-sniffing responses.',
                    'fix_steps' => [
                        'steps' => [
                            'Add X-Content-Type-Options header',
                            'Set value to nosniff',
                        ],
                        'snippet' => 'X-Content-Type-Options: nosniff',
                        'nginx_hint' => 'add_header X-Content-Type-Options "nosniff" always;',
                        'apache_hint' => 'Header always set X-Content-Type-Options "nosniff"',
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['security'] += $issue->score_penalty;
            }

            // SEC_MISSING_CSP
            if (!($headers['csp'] ?? false)) {
                $issue = $this->createIssueRecord([
                    'audit_id' => $audit->id,
                    'code' => 'SEC_MISSING_CSP',
                    'category' => 'security',
                    'title' => 'Missing Content-Security-Policy Header',
                    'description' => "Page {$page->url} is missing the Content-Security-Policy header. CSP helps prevent XSS attacks.",
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 10,
                    'affected_count' => 1,
                    'sample_urls' => [$page->url],
                    'recommendation' => 'Add Content-Security-Policy header to restrict resource loading and prevent XSS attacks.',
                    'fix_steps' => [
                        'steps' => [
                            'Create a CSP policy',
                            'Start with report-only mode',
                            'Gradually tighten restrictions',
                            'Switch to enforce mode once stable',
                        ],
                        'snippet' => 'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\';',
                        'nginx_hint' => 'add_header Content-Security-Policy "default-src \'self\'; script-src \'self\' \'unsafe-inline\';" always;',
                    ],
                ]);
                $issues[] = $issue;
                $categoryPenalties['security'] += $issue->score_penalty;
            }
        }

        return [
            'issues' => $issues,
            'categoryPenalties' => $categoryPenalties,
        ];
    }

    /**
     * Create an audit issue from a rule
     */
    protected function createIssue(Audit $audit, array $rule, AuditPage $page): AuditIssue
    {
        $recommendation = $this->getRecommendation($rule['code']);
        $fixSteps = $this->getFixSteps($rule['code']);

        return $this->createIssueRecord([
            'audit_id' => $audit->id,
            'code' => $rule['code'],
            'category' => $rule['category'] ?? 'onpage',
            'title' => $rule['title'],
            'description' => $rule['description'],
            'impact' => $rule['impact'],
            'effort' => $rule['effort'],
            'score_penalty' => $rule['penalty'],
            'affected_count' => 1,
            'sample_urls' => [$page->url],
            'recommendation' => $recommendation,
            'fix_steps' => $fixSteps,
        ]);
    }

    /**
     * Create an audit issue while removing fields that may not exist in DB.
     */
    protected function createIssueRecord(array $data): AuditIssue
    {
        unset($data['category'], $data['sample_urls']);

        return AuditIssue::create($data);
    }

    /**
     * Get recommendation text for a rule code
     */
    protected function getRecommendation(string $code): string
    {
        $recommendations = [
            'ONPAGE_TITLE_MISSING' => 'Add a descriptive <title> tag to your HTML <head> section.',
            'ONPAGE_TITLE_TOO_SHORT' => 'Expand your title to 30-60 characters to include more relevant keywords.',
            'ONPAGE_TITLE_TOO_LONG' => 'Shorten your title to 60 characters or less to prevent truncation.',
            'ONPAGE_META_DESCRIPTION_MISSING' => 'Add a meta description tag that summarizes your page content.',
            'ONPAGE_META_DESCRIPTION_TOO_SHORT' => 'Expand your meta description to 70-160 characters.',
            'ONPAGE_META_DESCRIPTION_TOO_LONG' => 'Shorten your meta description to 160 characters or less.',
            'ONPAGE_H1_MISSING' => 'Add a single H1 heading that describes the main topic of your page.',
            'ONPAGE_MULTIPLE_H1' => 'Use only one H1 tag per page. Convert additional H1 tags to H2 or H3.',
            'ONPAGE_IMAGES_MISSING_ALT' => 'Add descriptive alt text to all images that describes their content or purpose.',
            'ONPAGE_LOW_WORD_COUNT' => 'Add more valuable content to your page. Aim for at least 300 words of quality content.',
            'TECHNICAL_CANONICAL_MISSING' => 'Add a canonical URL tag pointing to the preferred version of this page.',
            'TECHNICAL_HTTPS_NOT_ENFORCED' => 'Configure your server to redirect HTTP to HTTPS and install an SSL certificate.',
            'SOCIAL_OG_TAGS_MISSING' => 'Add Open Graph meta tags (og:title, og:description, og:image) to improve social sharing.',
            'SOCIAL_TWITTER_CARDS_MISSING' => 'Add Twitter Card meta tags (twitter:card, twitter:title, twitter:description) for better Twitter sharing.',
            'PERFORMANCE_LARGE_HTML_SIZE' => 'Optimize your HTML by removing unnecessary code, minifying CSS/JS, and reducing inline styles.',
            'LINKS_TOO_MANY' => 'Reduce the number of links on the page. Consider consolidating or moving links to other pages.',
        ];

        return $recommendations[$code] ?? 'Review and fix this issue to improve your SEO score.';
    }

    /**
     * Get fix steps for a rule code
     */
    protected function getFixSteps(string $code): array
    {
        $steps = [
            'ONPAGE_TITLE_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the <head> section',
                    'Add: <title>Your Page Title Here</title>',
                ],
                'snippet' => '<title>Your Page Title Here</title>',
                'laravel_hint' => 'In Laravel Blade: <title>{{ $pageTitle }}</title>',
            ],
            'ONPAGE_META_DESCRIPTION_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the <head> section',
                    'Add: <meta name="description" content="Your description here">',
                ],
                'snippet' => '<meta name="description" content="Your description here">',
                'laravel_hint' => 'In Laravel Blade: <meta name="description" content="{{ $metaDescription }}">',
            ],
            'TECHNICAL_CANONICAL_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the <head> section',
                    'Add: <link rel="canonical" href="https://example.com/page">',
                ],
                'snippet' => '<link rel="canonical" href="https://example.com/page">',
                'laravel_hint' => 'In Laravel Blade: <link rel="canonical" href="{{ url()->current() }}">',
            ],
            'SOCIAL_OG_TAGS_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the <head> section',
                    'Add Open Graph meta tags',
                ],
                'snippet' => '<meta property="og:title" content="Page Title">' . "\n" .
                            '<meta property="og:description" content="Page Description">' . "\n" .
                            '<meta property="og:image" content="https://example.com/image.jpg">' . "\n" .
                            '<meta property="og:url" content="https://example.com/page">',
                'laravel_hint' => 'Use Laravel\'s SEO packages or add manually in Blade templates',
            ],
            'ONPAGE_TITLE_TOO_SHORT' => [
                'steps' => [
                    'Review your current title',
                    'Expand it to 30-60 characters',
                    'Include relevant keywords',
                ],
                'snippet' => '<title>Your Expanded Page Title with Keywords (30-60 chars)</title>',
                'laravel_hint' => 'Ensure your Blade title variable is 30-60 characters',
            ],
            'ONPAGE_TITLE_TOO_LONG' => [
                'steps' => [
                    'Review your current title',
                    'Shorten it to 60 characters or less',
                    'Keep the most important keywords',
                ],
                'snippet' => '<title>Shorter Title Under 60 Characters</title>',
                'laravel_hint' => 'Use Str::limit() in Laravel to truncate titles',
            ],
            'ONPAGE_META_DESCRIPTION_TOO_SHORT' => [
                'steps' => [
                    'Review your current meta description',
                    'Expand it to 70-160 characters',
                    'Make it compelling and descriptive',
                ],
                'snippet' => '<meta name="description" content="A longer, more descriptive meta description that is between 70 and 160 characters in length.">',
                'laravel_hint' => 'Ensure your meta description is 70-160 characters',
            ],
            'ONPAGE_META_DESCRIPTION_TOO_LONG' => [
                'steps' => [
                    'Review your current meta description',
                    'Shorten it to 160 characters or less',
                    'Keep the most compelling parts',
                ],
                'snippet' => '<meta name="description" content="A concise meta description under 160 characters.">',
                'laravel_hint' => 'Use Str::limit() in Laravel to truncate meta descriptions',
            ],
            'ONPAGE_H1_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the main content area',
                    'Add a single H1 tag describing the page topic',
                ],
                'snippet' => '<h1>Main Page Heading</h1>',
                'laravel_hint' => 'In Laravel Blade: <h1>{{ $pageHeading }}</h1>',
            ],
            'ONPAGE_MULTIPLE_H1' => [
                'steps' => [
                    'Find all H1 tags on the page',
                    'Keep only the main H1',
                    'Convert other H1 tags to H2 or H3',
                ],
                'snippet' => '<h1>Main Heading</h1>' . "\n" . '<h2>Secondary Heading</h2>',
                'laravel_hint' => 'Ensure only one H1 per page template',
            ],
            'ONPAGE_IMAGES_MISSING_ALT' => [
                'steps' => [
                    'Find all images without alt text',
                    'Add descriptive alt attributes',
                    'Describe what the image shows or its purpose',
                ],
                'snippet' => '<img src="image.jpg" alt="Descriptive alt text here">',
                'laravel_hint' => 'Always include alt attribute: <img src="{{ $image }}" alt="{{ $alt }}">',
            ],
            'ONPAGE_LOW_WORD_COUNT' => [
                'steps' => [
                    'Review your page content',
                    'Add valuable, relevant content',
                    'Aim for at least 300 words',
                ],
                'snippet' => null,
                'laravel_hint' => 'Add more content sections or expand existing ones',
            ],
            'TECHNICAL_HTTPS_NOT_ENFORCED' => [
                'steps' => [
                    'Install an SSL certificate on your server',
                    'Configure your web server to redirect HTTP to HTTPS',
                    'Update all internal links to use HTTPS',
                ],
                'snippet' => null,
                'laravel_hint' => 'Use Laravel\'s URL::forceScheme(\'https\') or configure server redirects',
            ],
            'SOCIAL_TWITTER_CARDS_MISSING' => [
                'steps' => [
                    'Open your HTML file or template',
                    'Locate the <head> section',
                    'Add Twitter Card meta tags',
                ],
                'snippet' => '<meta name="twitter:card" content="summary">' . "\n" .
                            '<meta name="twitter:title" content="Page Title">' . "\n" .
                            '<meta name="twitter:description" content="Page Description">',
                'laravel_hint' => 'Add Twitter Card meta tags in your Blade layout',
            ],
            'PERFORMANCE_LARGE_HTML_SIZE' => [
                'steps' => [
                    'Minify your HTML, CSS, and JavaScript',
                    'Remove unnecessary inline styles',
                    'Consider code splitting or lazy loading',
                ],
                'snippet' => null,
                'laravel_hint' => 'Use Laravel Mix/Vite to minify assets, remove unused code',
            ],
            'LINKS_TOO_MANY' => [
                'steps' => [
                    'Review all links on the page',
                    'Consolidate or remove unnecessary links',
                    'Move some links to other pages if needed',
                ],
                'snippet' => null,
                'laravel_hint' => 'Consider pagination or moving links to a sitemap page',
            ],
        ];

        return $steps[$code] ?? [
            'steps' => ['Review the issue description and implement the recommended fix.'],
            'snippet' => null,
            'laravel_hint' => null,
        ];
    }

    /**
     * Calculate category scores based on penalties
     * 
     * Start from 100 and subtract penalties, capped at 0
     */
    public function calculateCategoryScores(array $categoryPenalties): array
    {
        $scores = [];
        
        foreach ($categoryPenalties as $category => $penalty) {
            $score = max(0, 100 - $penalty);
            $scores[$category] = $score;
        }

        return $scores;
    }

    /**
     * Calculate overall score from category scores
     */
    public function calculateOverallScore(array $categoryScores): int
    {
        // Weighted average (onpage and technical are more important)
        // Updated weights: performance now has real weight, security included
        $weights = [
            'onpage' => 0.25,
            'technical' => 0.20,
            'performance' => 0.20, // Increased from 0.15
            'links' => 0.10,
            'social' => 0.08,
            'usability' => 0.07,
            'security' => 0.05,
            'local' => 0.05,
        ];

        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($weights as $category => $weight) {
            $score = $categoryScores[$category] ?? 70; // Default 70 if not calculated
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }

        return (int) round($weightedSum / $totalWeight);
    }

    /**
     * Convert score to grade
     */
    public function scoreToGrade(int $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}


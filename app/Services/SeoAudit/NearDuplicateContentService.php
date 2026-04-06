<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NearDuplicateContentService
{
    public function __construct(
        protected RulesEngine $rulesEngine
    ) {}

    public function run(Audit $audit): array
    {
        $pages = $audit->pages()->get()->filter(fn (AuditPage $page) => $this->isIndexable($page))->values();
        if ($pages->count() < 2) {
            return ['clusters' => [], 'pairs' => []];
        }

        $fingerprints = [];
        foreach ($pages as $page) {
            $normalized = $this->normalizedVisibleText($page);
            $hash = hash('sha256', $normalized);
            $page->content_fingerprint = $hash;
            $page->save();

            $fingerprints[$page->id] = [
                'page' => $page,
                'normalized' => $normalized,
                'hash' => $hash,
                'shingles' => $this->shingles($normalized),
            ];
        }

        $exactGroups = collect($fingerprints)->groupBy('hash')->filter(fn (Collection $g) => $g->count() > 1);
        $pairs = [];
        $adjacency = [];
        foreach ($pages as $page) {
            $adjacency[$page->id] = [$page->id => true];
        }

        foreach ($exactGroups as $hash => $group) {
            $values = $group->values();
            for ($i = 0; $i < $values->count(); $i++) {
                for ($j = $i + 1; $j < $values->count(); $j++) {
                    $a = $values[$i]['page'];
                    $b = $values[$j]['page'];
                    $pairs[] = ['a' => $a, 'b' => $b, 'score' => 1.0, 'type' => 'exact_duplicate'];
                    $adjacency[$a->id][$b->id] = true;
                    $adjacency[$b->id][$a->id] = true;
                }
            }
        }

        $ids = array_values(array_keys($fingerprints));
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $left = $fingerprints[$ids[$i]];
                $right = $fingerprints[$ids[$j]];
                if ($left['hash'] === $right['hash']) {
                    continue;
                }
                $score = $this->jaccard($left['shingles'], $right['shingles']);
                if ($score >= 0.82) {
                    $pairs[] = ['a' => $left['page'], 'b' => $right['page'], 'score' => $score, 'type' => 'near_duplicate'];
                    $adjacency[$left['page']->id][$right['page']->id] = true;
                    $adjacency[$right['page']->id][$left['page']->id] = true;
                }
            }
        }

        $clusters = $this->connectedComponents($adjacency, $fingerprints);
        foreach ($clusters as $cluster) {
            foreach ($cluster['members'] as $member) {
                $member->near_duplicate_cluster_id = $cluster['cluster_id'];
                $member->save();
            }
            $this->persistClusterIssue($audit, $cluster);
        }

        foreach ($pairs as $pair) {
            if ($pair['type'] !== 'near_duplicate') {
                continue;
            }
            $thin = (($pair['a']->word_count ?? 0) < 300) || (($pair['b']->word_count ?? 0) < 300);
            if ($thin) {
                $this->rulesEngine->createCustomIssue($audit, [
                    'module_key' => 'near_duplicate_content',
                    'code' => 'NEAR_DUPLICATE_THIN_COMBINATION',
                    'category' => 'onpage',
                    'title' => 'Thin + near duplicate page pair detected',
                    'description' => 'A near-duplicate pair also includes thin content, increasing cannibalization risk.',
                    'impact' => AuditIssue::IMPACT_MEDIUM,
                    'effort' => AuditIssue::EFFORT_MEDIUM,
                    'score_penalty' => 8,
                    'affected_count' => 2,
                    'sample_urls' => [$pair['a']->url, $pair['b']->url],
                    'url' => $pair['a']->url,
                    'recommendation' => 'Consolidate or rewrite one of the pages. Keep one canonical URL with stronger unique content.',
                    'details_json' => [
                        'pair' => [$pair['a']->url, $pair['b']->url],
                        'similarity' => round($pair['score'], 4),
                        'type' => 'thin_near_duplicate',
                    ],
                ]);
            }
        }

        $strongPairs = collect($pairs)->sortByDesc('score')->take(25)->map(fn ($pair) => [
            'left_url' => $pair['a']->url,
            'right_url' => $pair['b']->url,
            'similarity' => round($pair['score'], 4),
            'type' => $pair['type'],
        ])->values()->toArray();

        return [
            'clusters' => array_values($clusters),
            'pairs' => $strongPairs,
        ];
    }

    protected function persistClusterIssue(Audit $audit, array $cluster): void
    {
        $isExact = ($cluster['max_similarity'] ?? 0) >= 0.9999;
        $code = $isExact ? 'DUPLICATE_CLUSTER_EXACT' : 'DUPLICATE_CLUSTER_NEAR';
        $impact = $cluster['size'] >= 4 ? AuditIssue::IMPACT_HIGH : AuditIssue::IMPACT_MEDIUM;

        $this->rulesEngine->createCustomIssue($audit, [
            'module_key' => 'near_duplicate_content',
            'code' => $code,
            'category' => 'onpage',
            'title' => $isExact ? 'Exact duplicate content cluster' : 'Near duplicate content cluster',
            'description' => "Cluster {$cluster['cluster_id']} contains {$cluster['size']} similar pages.",
            'impact' => $impact,
            'effort' => AuditIssue::EFFORT_MEDIUM,
            'score_penalty' => min(16, max(4, (int) ($cluster['size'] * 2))),
            'affected_count' => $cluster['size'],
            'sample_urls' => $cluster['member_urls'],
            'url' => $cluster['canonical_url'],
            'recommendation' => 'Choose a representative URL, canonicalize duplicates, and merge or differentiate overlapping pages.',
            'details_json' => [
                'cluster_id' => $cluster['cluster_id'],
                'canonical_url' => $cluster['canonical_url'],
                'member_urls' => $cluster['member_urls'],
                'max_similarity' => $cluster['max_similarity'],
                'strongest_pair' => $cluster['strongest_pair'],
            ],
        ]);
    }

    protected function connectedComponents(array $adj, array $fingerprints): array
    {
        $seen = [];
        $clusters = [];
        foreach (array_keys($adj) as $node) {
            if (isset($seen[$node])) {
                continue;
            }
            $stack = [$node];
            $members = [];
            while ($stack !== []) {
                $current = array_pop($stack);
                if (isset($seen[$current])) {
                    continue;
                }
                $seen[$current] = true;
                $members[] = $fingerprints[$current]['page'];
                foreach (array_keys($adj[$current] ?? []) as $neighbor) {
                    if (!isset($seen[$neighbor])) {
                        $stack[] = $neighbor;
                    }
                }
            }
            if (count($members) < 2) {
                continue;
            }
            usort($members, fn (AuditPage $a, AuditPage $b) => ($b->internal_links_count ?? 0) <=> ($a->internal_links_count ?? 0));
            $canonical = $members[0];
            $memberUrls = array_map(fn (AuditPage $page) => $page->url, $members);
            $clusterId = 'ndc_' . substr(hash('sha1', implode('|', $memberUrls)), 0, 10);

            $max = 0.0;
            $pair = null;
            for ($i = 0; $i < count($members); $i++) {
                for ($j = $i + 1; $j < count($members); $j++) {
                    $a = $fingerprints[$members[$i]->id]['shingles'];
                    $b = $fingerprints[$members[$j]->id]['shingles'];
                    $score = $this->jaccard($a, $b);
                    if ($score > $max) {
                        $max = $score;
                        $pair = [$members[$i]->url, $members[$j]->url];
                    }
                }
            }

            $clusters[] = [
                'cluster_id' => $clusterId,
                'canonical_url' => $canonical->url,
                'size' => count($members),
                'member_urls' => $memberUrls,
                'members' => $members,
                'max_similarity' => round($max, 4),
                'strongest_pair' => $pair,
            ];
        }

        return $clusters;
    }

    protected function normalizedVisibleText(AuditPage $page): string
    {
        $base = implode(' ', array_filter([
            $page->title,
            $page->h1_text,
            $page->meta_description,
            $page->content_excerpt,
        ]));
        $text = Str::lower(strip_tags($base));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?: '';
        $text = preg_replace('/\s+/', ' ', trim($text)) ?: '';

        return $text;
    }

    protected function shingles(string $text, int $size = 3): array
    {
        $tokens = array_values(array_filter(explode(' ', $text)));
        if (count($tokens) < $size) {
            return $tokens === [] ? [] : [implode(' ', $tokens)];
        }
        $out = [];
        for ($i = 0; $i <= count($tokens) - $size; $i++) {
            $out[] = implode(' ', array_slice($tokens, $i, $size));
        }

        return array_values(array_unique($out));
    }

    protected function jaccard(array $a, array $b): float
    {
        if ($a === [] && $b === []) {
            return 1.0;
        }
        if ($a === [] || $b === []) {
            return 0.0;
        }
        $aa = array_fill_keys($a, true);
        $bb = array_fill_keys($b, true);
        $inter = count(array_intersect_key($aa, $bb));
        $union = count($aa) + count($bb) - $inter;

        return $union > 0 ? ($inter / $union) : 0.0;
    }

    protected function isIndexable(AuditPage $page): bool
    {
        $status = (int) ($page->status_code ?? 0);
        if ($status >= 300) {
            return false;
        }
        $robots = strtolower($page->robots_meta ?? '');
        $xRobots = strtolower($page->x_robots_tag ?? '');

        return !str_contains($robots, 'noindex') && !str_contains($xRobots, 'noindex');
    }
}


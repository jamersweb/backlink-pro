<?php

namespace App\Services\Content;

use App\Models\Domain;
use App\Models\GscTopQuery;
use App\Models\GscTopPage;
use App\Models\KeywordMap;
use Illuminate\Support\Str;

class BriefGenerator
{
    /**
     * Generate a content brief
     */
    public function generate(Domain $domain, string $primaryKeyword, ?string $targetUrl = null, string $targetType = 'new_page'): array
    {
        // Get related queries from GSC
        $relatedQueries = $this->getRelatedQueries($domain, $primaryKeyword);

        // Detect intent
        $intent = $this->detectIntent($primaryKeyword, $relatedQueries);

        // Generate title
        $title = $this->generateTitle($primaryKeyword);

        // Generate outline
        $outline = $this->generateOutline($primaryKeyword, $intent, $relatedQueries);

        // Generate FAQs
        $faqs = $this->generateFaqs($primaryKeyword, $relatedQueries);

        // Get internal links
        $internalLinks = $this->getInternalLinks($domain, $primaryKeyword);

        // Generate meta suggestions
        $metaSuggestion = $this->generateMetaSuggestion($domain, $primaryKeyword, $title);

        // Check for cannibalization
        $cannibalizationWarning = $this->checkCannibalization($domain, $primaryKeyword, $targetUrl);

        return [
            'title' => $title,
            'primary_keyword' => $primaryKeyword,
            'secondary_keywords' => array_slice($relatedQueries, 0, 5),
            'intent' => $intent,
            'outline_json' => $outline,
            'faq_json' => $faqs,
            'internal_links_json' => $internalLinks,
            'meta_suggestion_json' => $metaSuggestion,
            'cannibalization_warning' => $cannibalizationWarning,
        ];
    }

    /**
     * Get related queries from GSC
     */
    protected function getRelatedQueries(Domain $domain, string $primaryKeyword): array
    {
        // Get queries that contain similar words or are semantically related
        $keywords = explode(' ', strtolower($primaryKeyword));
        
        $queries = GscTopQuery::where('domain_id', $domain->id)
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('query', 'like', "%{$keyword}%");
                }
            })
            ->orderByDesc('impressions')
            ->limit(20)
            ->pluck('query')
            ->toArray();

        // Remove the primary keyword itself
        $queries = array_filter($queries, fn($q) => strtolower($q) !== strtolower($primaryKeyword));

        return array_values(array_unique($queries));
    }

    /**
     * Detect search intent
     */
    protected function detectIntent(string $keyword, array $relatedQueries): string
    {
        $keywordLower = strtolower($keyword);
        $allText = strtolower($keyword . ' ' . implode(' ', $relatedQueries));

        // Transactional signals
        if (preg_match('/\b(buy|purchase|price|cost|cheap|discount|deal|sale|order|shop|store)\b/i', $allText)) {
            return 'transactional';
        }

        // Navigational signals
        if (preg_match('/\b(login|sign in|account|dashboard|portal)\b/i', $allText)) {
            return 'navigational';
        }

        // Mixed signals
        if (preg_match('/\b(how|what|why|when|where|guide|tutorial|best|review|compare)\b/i', $allText) &&
            preg_match('/\b(buy|price|cost)\b/i', $allText)) {
            return 'mixed';
        }

        // Default: informational
        return 'informational';
    }

    /**
     * Generate title
     */
    protected function generateTitle(string $keyword): string
    {
        // Title case the keyword
        return Str::title($keyword);
    }

    /**
     * Generate outline based on intent
     */
    protected function generateOutline(string $keyword, string $intent, array $relatedQueries): array
    {
        $outline = [
            [
                'level' => 1,
                'heading' => $this->generateTitle($keyword),
                'content' => "Introduction to {$keyword}. This section provides an overview and defines key concepts.",
            ],
        ];

        if ($intent === 'informational' || $intent === 'mixed') {
            $outline[] = [
                'level' => 2,
                'heading' => 'What is ' . $keyword . '?',
                'content' => 'Definition and explanation.',
            ];

            $outline[] = [
                'level' => 2,
                'heading' => 'Benefits and Features',
                'content' => 'Key benefits and important features.',
            ];

            $outline[] = [
                'level' => 2,
                'heading' => 'How to Use ' . $this->generateTitle($keyword),
                'content' => 'Step-by-step guide or instructions.',
            ];

            $outline[] = [
                'level' => 2,
                'heading' => 'Common Mistakes to Avoid',
                'content' => 'Important pitfalls and how to avoid them.',
            ];
        }

        if ($intent === 'transactional' || $intent === 'mixed') {
            $outline[] = [
                'level' => 2,
                'heading' => 'Best ' . $this->generateTitle($keyword) . ' Options',
                'content' => 'Top recommendations and comparisons.',
            ];

            $outline[] = [
                'level' => 2,
                'heading' => 'Pricing and Value',
                'content' => 'Cost considerations and value proposition.',
            ];
        }

        // Add related topics from queries
        if (!empty($relatedQueries)) {
            $relatedSection = [
                'level' => 2,
                'heading' => 'Related Topics',
                'content' => 'Additional information on related subjects.',
                'subsections' => [],
            ];

            foreach (array_slice($relatedQueries, 0, 3) as $related) {
                $relatedSection['subsections'][] = [
                    'level' => 3,
                    'heading' => $this->generateTitle($related),
                    'content' => 'Information about ' . $related . '.',
                ];
            }

            $outline[] = $relatedSection;
        }

        $outline[] = [
            'level' => 2,
            'heading' => 'Frequently Asked Questions',
            'content' => 'Common questions and answers.',
        ];

        $outline[] = [
            'level' => 2,
            'heading' => 'Conclusion',
            'content' => 'Summary and call-to-action.',
        ];

        return $outline;
    }

    /**
     * Generate FAQs
     */
    protected function generateFaqs(string $keyword, array $relatedQueries): array
    {
        $faqs = [];

        // Generate 3-5 FAQs based on keyword and related queries
        $questionTemplates = [
            "What is {$keyword}?",
            "How does {$keyword} work?",
            "Why is {$keyword} important?",
            "When should I use {$keyword}?",
            "Where can I find {$keyword}?",
        ];

        foreach (array_slice($questionTemplates, 0, 3) as $question) {
            $faqs[] = [
                'question' => $question,
                'answer' => "This section should provide a comprehensive answer to: {$question}",
            ];
        }

        // Add FAQs from related queries
        foreach (array_slice($relatedQueries, 0, 2) as $related) {
            $faqs[] = [
                'question' => "How does {$keyword} relate to {$related}?",
                'answer' => "Explanation of the relationship between {$keyword} and {$related}.",
            ];
        }

        return $faqs;
    }

    /**
     * Get internal links from top pages
     */
    protected function getInternalLinks(Domain $domain, string $keyword): array
    {
        // Get top pages from GSC
        $topPages = GscTopPage::where('domain_id', $domain->id)
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        $links = [];
        foreach ($topPages->take(5) as $page) {
            $links[] = [
                'url' => $page->page,
                'anchor' => $this->generateAnchorText($page->page, $keyword),
            ];
        }

        return $links;
    }

    /**
     * Generate anchor text for internal link
     */
    protected function generateAnchorText(string $url, string $keyword): string
    {
        // Extract last part of URL path as anchor
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $parts = explode('/', trim($path, '/'));
            $lastPart = end($parts);
            if ($lastPart) {
                return Str::title(str_replace(['-', '_'], ' ', $lastPart));
            }
        }

        return 'Learn more';
    }

    /**
     * Generate meta suggestion
     */
    protected function generateMetaSuggestion(Domain $domain, string $keyword, string $title): array
    {
        $brand = $domain->name ?? 'Your Site';
        
        // Meta title: Primary Keyword | Brand (max 60 chars)
        $metaTitle = $title . ' | ' . $brand;
        if (strlen($metaTitle) > 60) {
            $metaTitle = substr($title, 0, 60 - strlen(' | ' . $brand)) . ' | ' . $brand;
        }

        // Meta description: 150-160 chars
        $metaDescription = "Learn everything about {$keyword}. " . 
                          "Discover benefits, features, and best practices. " .
                          "Get expert insights and actionable tips.";
        
        if (strlen($metaDescription) > 160) {
            $metaDescription = substr($metaDescription, 0, 157) . '...';
        }

        return [
            'title' => $metaTitle,
            'description' => $metaDescription,
        ];
    }

    /**
     * Check for keyword cannibalization
     */
    protected function checkCannibalization(Domain $domain, string $keyword, ?string $targetUrl): ?array
    {
        $existing = KeywordMap::where('domain_id', $domain->id)
            ->where('keyword', $keyword)
            ->first();

        if ($existing && $existing->url !== $targetUrl) {
            return [
                'warning' => true,
                'message' => "Keyword '{$keyword}' is already mapped to: {$existing->url}",
                'existing_url' => $existing->url,
            ];
        }

        return null;
    }
}


<?php

namespace App\Services\SeoAudit;

/**
 * Lightweight dictionary + edit-distance-1 suggestions for English token spellcheck.
 */
class SpellingDictionary
{
    /** @var array<string, true> */
    protected array $known = [];

    /** @var array<int, list<string>> */
    protected array $byLength = [];

    /**
     * @param  list<string>  $allowlistLower  Extra known terms (already lowercased)
     */
    public function __construct(array $allowlistLower = [])
    {
        $path = config('seo_audit.spelling.dictionary_path');
        $words = [];
        if (is_string($path) && $path !== '' && is_readable($path)) {
            $raw = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $words = is_array($raw) ? $raw : [];
        }
        if ($words === []) {
            $words = self::embeddedFallback();
        }

        foreach ($words as $w) {
            $this->addWord(strtolower(trim($w)));
        }
        foreach ($allowlistLower as $w) {
            $this->addWord(strtolower(trim($w)));
        }
    }

    protected function addWord(string $w): void
    {
        if ($w === '' || str_contains($w, ' ')) {
            return;
        }
        $this->known[$w] = true;
        $len = strlen($w);
        $this->byLength[$len][] = $w;
    }

    public function isKnown(string $token): bool
    {
        $w = strtolower($token);
        if ($w === '' || $this->looksLikeNonWord($w)) {
            return true;
        }

        if (isset($this->known[$w])) {
            return true;
        }

        if (preg_match("/^([a-z]+)'s$/", $w, $m)) {
            if (isset($this->known[$m[1]])) {
                return true;
            }
        }

        if (preg_match('/^[a-z]{2,}s$/', $w) && isset($this->known[substr($w, 0, -1)])) {
            return true;
        }

        if (preg_match('/^[a-z]{3,}es$/', $w) && isset($this->known[substr($w, 0, -2)])) {
            return true;
        }

        if (preg_match('/^[a-z]{3,}ed$/', $w) && isset($this->known[substr($w, 0, -2)])) {
            return true;
        }
        if (preg_match('/^[a-z]{3,}ed$/', $w) && isset($this->known[substr($w, 0, -1)])) {
            return true;
        }

        if (preg_match('/^[a-z]{3,}ing$/', $w) && strlen($w) > 5) {
            $stem = substr($w, 0, -3);
            if (isset($this->known[$stem]) || isset($this->known[$stem.'e'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Best Levenshtein-1 suggestion among similar-length dictionary entries (ASCII-safe subset).
     */
    public function suggest(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }
        $w = strtolower($token);
        if ($this->looksLikeNonWord($w) || strlen($w) < 4) {
            return null;
        }

        if (!preg_match('/^[a-z]+$/', $w)) {
            return null;
        }

        $len = strlen($w);
        $candidates = array_merge(
            $this->byLength[$len] ?? [],
            $this->byLength[$len - 1] ?? [],
            $this->byLength[$len + 1] ?? []
        );

        foreach ($candidates as $c) {
            if ($c === $w || strlen($c) < 3) {
                continue;
            }
            if (levenshtein($w, $c) === 1) {
                return $c;
            }
        }

        return null;
    }

    protected function looksLikeNonWord(string $w): bool
    {
        if (strlen($w) > 24) {
            return true;
        }

        return (bool) preg_match('/\d/', $w);
    }

    /**
     * @return list<string>
     */
    protected static function embeddedFallback(): array
    {
        return [
            'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i',
            'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at',
            'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she',
            'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their',
            'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go',
            'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him',
            'know', 'take', 'people', 'into', 'year', 'your', 'good', 'some',
            'could', 'them', 'see', 'other', 'than', 'then', 'now', 'look',
            'only', 'come', 'its', 'over', 'think', 'also', 'back', 'after',
            'use', 'two', 'how', 'our', 'work', 'first', 'well', 'way', 'even',
            'new', 'want', 'because', 'any', 'these', 'give', 'day', 'most',
            'us', 'is', 'was', 'are', 'were', 'been', 'being', 'has', 'had',
            'does', 'did', 'quick', 'brown', 'fox', 'jumps', 'lazy', 'dog',
            'content', 'quality', 'page', 'text', 'title', 'receive', 'received',
            'occurred', 'separate', 'definitely', 'accommodate', 'maintain', 'available',
        ];
    }
}

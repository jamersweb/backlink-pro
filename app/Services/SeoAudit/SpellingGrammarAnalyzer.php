<?php

namespace App\Services\SeoAudit;

/**
 * Heuristic spelling, grammar, repetition, and punctuation checks with confidence scoring.
 */
class SpellingGrammarAnalyzer
{
    /** @var array<string, string> */
    protected const COMMON_TYPOS = [
        'teh' => 'the',
        'adn' => 'and',
        'taht' => 'that',
        'hte' => 'the',
        'recieve' => 'receive',
        'recieved' => 'received',
        'occured' => 'occurred',
        'occurence' => 'occurrence',
        'definately' => 'definitely',
        'wierd' => 'weird',
        'acheive' => 'achieve',
        'beleive' => 'believe',
        'calender' => 'calendar',
        'cemetary' => 'cemetery',
        'commited' => 'committed',
        'embarass' => 'embarrass',
        'freind' => 'friend',
        'goverment' => 'government',
        'independant' => 'independent',
        'knowlege' => 'knowledge',
        'litre' => 'liter',
        'maintainance' => 'maintenance',
        'medevil' => 'medieval',
        'neccessary' => 'necessary',
        'noticable' => 'noticeable',
        'occassion' => 'occasion',
        'persistant' => 'persistent',
        'posession' => 'possession',
        'recomend' => 'recommend',
        'refered' => 'referred',
        'religous' => 'religious',
        'speach' => 'speech',
        'tommorow' => 'tomorrow',
        'truely' => 'truly',
        'untill' => 'until',
        'writting' => 'writing',
        'thier' => 'their',
    ];

    public function __construct(
        protected SpellingDictionary $dictionary,
        protected array $allowlistLower,
    ) {}

    /**
     * @return list<array{kind: string, text: string, suggestion: ?string, confidence: int, offset: int, context: string, filter_tags: list<string>}>
     */
    public function analyze(string $text): array
    {
        $minConf = (int) config('seo_audit.spelling.min_confidence', 62);
        $stripped = $this->stripUrlsEmails($text);
        $stripped = $this->stripLikelyCodeTokens($stripped);

        $out = [];
        $out = array_merge($out, $this->findRepeatedWords($stripped));
        $out = array_merge($out, $this->findPunctuation($stripped));
        $out = array_merge($out, $this->findGrammarPatterns($stripped));
        $out = array_merge($out, $this->findSpelling($stripped));

        $filtered = [];
        foreach ($out as $row) {
            if (($row['confidence'] ?? 0) < $minConf) {
                continue;
            }
            $tags = $row['filter_tags'] ?? [];
            if (($row['confidence'] ?? 0) >= (int) config('seo_audit.spelling.high_confidence', 78)) {
                $tags[] = 'high_confidence';
            }
            $row['filter_tags'] = array_values(array_unique($tags));
            $filtered[] = $row;
        }

        return $this->dedupe($filtered);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findRepeatedWords(string $text): array
    {
        $findings = [];
        if (preg_match_all('/\b([a-z]{2,})\s+\1\b/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $i => $hit) {
                $word = strtolower($m[1][$i][0]);
                if ($this->isAllowlisted($word)) {
                    continue;
                }
                if (in_array($word, ['that', 'very', 'so', 'no'], true)) {
                    continue;
                }
                $findings[] = [
                    'kind' => 'repeated_word',
                    'text' => $hit[0],
                    'suggestion' => $m[1][$i][0],
                    'confidence' => 88,
                    'offset' => (int) $hit[1],
                    'context' => $this->snippet($text, (int) $hit[1], strlen($hit[0])),
                    'filter_tags' => ['repeated_word'],
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findPunctuation(string $text): array
    {
        $findings = [];
        if (preg_match_all('/\s+([.,;:!?])(?=\s|$)/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $idx => $hit) {
                $findings[] = [
                    'kind' => 'punctuation',
                    'text' => trim($hit[0]),
                    'suggestion' => trim($m[1][$idx][0]),
                    'confidence' => 72,
                    'offset' => (int) $hit[1],
                    'context' => $this->snippet($text, (int) $hit[1], strlen($hit[0])),
                    'filter_tags' => ['punctuation'],
                ];
            }
        }

        if (preg_match_all('/,([A-Za-z])/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $idx => $hit) {
                $findings[] = [
                    'kind' => 'punctuation',
                    'text' => $hit[0],
                    'suggestion' => ', '.$m[1][$idx][0],
                    'confidence' => 68,
                    'offset' => (int) $hit[1],
                    'context' => $this->snippet($text, (int) $hit[1], strlen($hit[0])),
                    'filter_tags' => ['punctuation'],
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findGrammarPatterns(string $text): array
    {
        $findings = [];
        $patterns = [
            [
                'rx' => '/\bit\'s\s+(own|first|last|name)\b/i',
                'suggestion' => 'its',
                'label' => 'Possessive "its" is written without an apostrophe.',
                'conf' => 74,
            ],
            [
                'rx' => '/\bits\s+is\b/i',
                'suggestion' => "it's",
                'label' => 'Contraction may be required: "it\'s" instead of "its is".',
                'conf' => 70,
            ],
            [
                'rx' => '/\s{2,}[,.]/u',
                'suggestion' => 'single space',
                'label' => 'Extra space before punctuation.',
                'conf' => 66,
            ],
        ];

        foreach ($patterns as $p) {
            if (preg_match_all($p['rx'], $text, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as $hit) {
                    $findings[] = [
                        'kind' => 'grammar',
                        'text' => $hit[0],
                        'suggestion' => $p['suggestion'],
                        'confidence' => $p['conf'],
                        'offset' => (int) $hit[1],
                        'context' => $this->snippet($text, (int) $hit[1], strlen($hit[0])),
                        'filter_tags' => ['grammar'],
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findSpelling(string $text): array
    {
        $findings = [];
        if (!preg_match_all('/\b[a-zA-Z]+\b/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        foreach ($m[0] as $i => $hit) {
            $raw = $hit[0];
            $lower = strtolower($raw);
            if (strlen($lower) < 3 || $this->isAllowlisted($lower)) {
                continue;
            }
            if ($this->dictionary->isKnown($raw)) {
                continue;
            }

            $suggestion = self::COMMON_TYPOS[$lower] ?? $this->dictionary->suggest($raw);
            if ($suggestion === null) {
                continue;
            }

            $confidence = 70;
            if (isset(self::COMMON_TYPOS[$lower])) {
                $confidence = 86;
            }
            if (strtolower($suggestion) === $lower) {
                continue;
            }
            if (strlen($lower) >= 6) {
                $confidence += 4;
            }
            if ($raw !== '' && ctype_upper($raw[0]) && !ctype_upper($raw)) {
                $confidence -= 12;
            }

            $findings[] = [
                'kind' => 'spelling',
                'text' => $raw,
                'suggestion' => $suggestion,
                'confidence' => min(95, max(55, $confidence)),
                'offset' => (int) $hit[1],
                'context' => $this->snippet($text, (int) $hit[1], strlen($raw)),
                'filter_tags' => ['spelling'],
            ];
        }

        return $findings;
    }

    protected function isAllowlisted(string $lower): bool
    {
        return in_array($lower, $this->allowlistLower, true);
    }

    protected function stripUrlsEmails(string $text): string
    {
        $text = preg_replace('/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/u', ' ', $text) ?? $text;
        $text = preg_replace('#https?://[^\s<>"\']+#iu', ' ', $text) ?? $text;
        $text = preg_replace('#www\.[^\s<>"\']+#iu', ' ', $text) ?? $text;

        return $text;
    }

    protected function stripLikelyCodeTokens(string $text): string
    {
        $text = preg_replace('/\b(?:var|let|const|function|return|null|undefined|true|false)\b/', ' ', $text) ?? $text;
        $text = preg_replace('/\b[a-z]+(?:_[a-z]+)+\b/', ' ', $text) ?? $text;
        $text = preg_replace('/\b[a-z]+(?:[A-Z][a-z0-9]+)+\b/', ' ', $text) ?? $text;

        return $text;
    }

    protected function snippet(string $text, int $offset, int $len, int $radius = 52): string
    {
        $start = max(0, $offset - $radius);
        $end = min(strlen($text), $offset + $len + $radius);
        $s = substr($text, $start, $end - $start);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function dedupe(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $key = ($row['offset'] ?? 0).':'.($row['kind'] ?? '').':'.strtolower((string) ($row['text'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        return $out;
    }
}

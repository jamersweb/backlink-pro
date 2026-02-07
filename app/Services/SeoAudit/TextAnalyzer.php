<?php

namespace App\Services\SeoAudit;

class TextAnalyzer
{
    protected static array $stopwords = [
        'the','and','for','with','that','this','from','your','you','are','was','were','will','have','has','had',
        'not','but','about','into','over','under','above','below','between','after','before','while','where','when',
        'who','whom','what','which','why','how','can','could','should','would','may','might','our','their','them',
        'they','she','him','his','her','its','a','an','in','on','at','to','of','by','as','is','it','be','or',
        'we','us','if','than','then','so','no','yes','do','does','did','just','more','most','less','least','up',
        'down','out','off','near','new','all','any','each','other','only','also','very','too','via','per'
    ];

    public static function topKeywords(string $text, int $limit = 10): array
    {
        $words = self::tokenize($text);
        $counts = [];

        foreach ($words as $word) {
            if (strlen($word) < 3) {
                continue;
            }
            if (in_array($word, self::$stopwords, true)) {
                continue;
            }
            if (is_numeric($word)) {
                continue;
            }
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);

        $result = [];
        foreach ($top as $keyword => $frequency) {
            $result[] = [
                'keyword' => $keyword,
                'frequency' => $frequency,
            ];
        }

        return $result;
    }

    public static function topPhrases(string $text, int $limit = 10, int $size = 2): array
    {
        $words = self::tokenize($text);
        $phrases = [];

        for ($i = 0; $i <= count($words) - $size; $i++) {
            $chunk = array_slice($words, $i, $size);
            if (count(array_intersect($chunk, self::$stopwords)) > 0) {
                continue;
            }
            $phrase = implode(' ', $chunk);
            if (strlen($phrase) < 5) {
                continue;
            }
            $phrases[$phrase] = ($phrases[$phrase] ?? 0) + 1;
        }

        arsort($phrases);
        $top = array_slice($phrases, 0, $limit, true);

        $result = [];
        foreach ($top as $phrase => $frequency) {
            $result[] = [
                'phrase' => $phrase,
                'frequency' => $frequency,
            ];
        }

        return $result;
    }

    protected static function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\\s]+/i', ' ', $text);
        $parts = preg_split('/\\s+/', trim($text));
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }
}

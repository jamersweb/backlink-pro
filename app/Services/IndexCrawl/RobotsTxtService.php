<?php

namespace App\Services\IndexCrawl;

class RobotsTxtService
{
    /**
     * Parse robots.txt content into directives for wildcard user-agent.
     */
    public function parse(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $activeAgents = [];
        $rules = [];
        $sitemaps = [];

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/\s*#.*$/', '', $line));
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directiveLower = strtolower($directive);

            if ($directiveLower === 'user-agent') {
                $agent = strtolower($value);
                $activeAgents = [$agent];
                $rules[$agent] = $rules[$agent] ?? ['allow' => [], 'disallow' => []];
                continue;
            }

            if ($directiveLower === 'sitemap') {
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    $sitemaps[] = $value;
                }
                continue;
            }

            if (!in_array($directiveLower, ['allow', 'disallow'], true) || empty($activeAgents)) {
                continue;
            }

            foreach ($activeAgents as $agent) {
                $rules[$agent][$directiveLower][] = $value;
            }
        }

        return [
            'rules' => $rules,
            'sitemaps' => array_values(array_unique($sitemaps)),
        ];
    }

    /**
     * Decide whether URL path is blocked for crawling.
     */
    public function isBlocked(string $urlPath, array $parsedRules): bool
    {
        $rules = $parsedRules['rules'] ?? [];
        $candidateRules = [];

        if (!empty($rules['*'])) {
            $candidateRules = $rules['*'];
        } elseif (!empty($rules['backlinkprobot'])) {
            $candidateRules = $rules['backlinkprobot'];
        }

        if (empty($candidateRules)) {
            return false;
        }

        $path = $urlPath ?: '/';
        $allowMatchLength = -1;
        foreach (($candidateRules['allow'] ?? []) as $pattern) {
            if ($pattern === '') {
                continue;
            }
            if ($this->matches($path, $pattern)) {
                $allowMatchLength = max($allowMatchLength, strlen($pattern));
            }
        }

        $disallowMatchLength = -1;
        foreach (($candidateRules['disallow'] ?? []) as $pattern) {
            if ($pattern === '') {
                continue;
            }
            if ($this->matches($path, $pattern)) {
                $disallowMatchLength = max($disallowMatchLength, strlen($pattern));
            }
        }

        if ($disallowMatchLength === -1) {
            return false;
        }

        return $disallowMatchLength > $allowMatchLength;
    }

    protected function matches(string $path, string $pattern): bool
    {
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        $regex = str_replace('\$', '$', $regex);
        if (!str_ends_with($regex, '$')) {
            $regex .= '.*';
        }

        return (bool) preg_match('/^' . $regex . '/i', $path);
    }
}

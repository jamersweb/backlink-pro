<?php

namespace App\Services\Automation;

class DecisionEngine
{
    /**
     * Decide action for a target URL
     */
    public function decideAction(string $url, array $allowedActions = ['comment', 'profile', 'forum', 'guest']): ?string
    {
        $urlLower = strtolower($url);
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // Rule 1: Profile pages
        if (in_array('profile', $allowedActions)) {
            if (preg_match('#/(profile|user|author|member|account)/#i', $path) ||
                preg_match('#/user-?[0-9]+#i', $path) ||
                str_contains($path, '/profile') ||
                str_contains($path, '/user/')) {
                return 'profile';
            }
        }

        // Rule 2: Comment sections
        if (in_array('comment', $allowedActions)) {
            if (str_contains($urlLower, '#comment') ||
                str_contains($path, '/comment') ||
                str_contains($urlLower, 'comment') ||
                preg_match('#/post/[^/]+#i', $path)) {
                return 'comment';
            }
        }

        // Rule 3: Forum/Thread pages
        if (in_array('forum', $allowedActions)) {
            if (preg_match('#/(forum|forums|thread|threads|topic|topics)/#i', $path) ||
                str_contains($path, '/forum') ||
                str_contains($path, '/thread')) {
                return 'forum';
            }
        }

        // Rule 4: Guest post (fallback)
        if (in_array('guest', $allowedActions)) {
            return 'guest';
        }

        // If no match and guest not allowed, return first allowed action
        return !empty($allowedActions) ? $allowedActions[0] : null;
    }

    /**
     * Detect platform from URL
     */
    public function detectPlatform(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $hostLower = strtolower($host);

        if (str_contains($hostLower, 'wordpress.com') || str_contains($hostLower, 'wp-')) {
            return 'wordpress';
        }

        if (str_contains($hostLower, 'blogspot') || str_contains($hostLower, 'blogger')) {
            return 'blogger';
        }

        if (str_contains($hostLower, 'medium.com')) {
            return 'medium';
        }

        if (str_contains($hostLower, 'reddit.com')) {
            return 'reddit';
        }

        if (preg_match('#\.(phpbb|vbulletin|mybb|smf)\.#i', $hostLower)) {
            return 'forum';
        }

        return null;
    }
}



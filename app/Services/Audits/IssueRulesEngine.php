<?php

namespace App\Services\Audits;

use App\Models\DomainAuditPage;

class IssueRulesEngine
{
    /**
     * Generate issues for a page
     */
    public static function generateIssues(DomainAuditPage $page): array
    {
        $issues = [];

        // CRITICAL issues
        if ($page->status_code >= 400) {
            $issues[] = [
                'severity' => 'critical',
                'type' => 'status_4xx_5xx',
                'message' => "Page returns {$page->status_code} status code",
                'data' => ['status_code' => $page->status_code],
            ];
        }

        if (!$page->is_indexable && strpos(strtolower($page->robots_meta ?? ''), 'noindex') !== false) {
            $issues[] = [
                'severity' => 'critical',
                'type' => 'not_indexable_noindex',
                'message' => 'Page has noindex directive in robots meta tag',
                'data' => ['robots_meta' => $page->robots_meta],
            ];
        }

        if (empty($page->title)) {
            $issues[] = [
                'severity' => 'critical',
                'type' => 'missing_title',
                'message' => 'Page is missing a title tag',
                'data' => [],
            ];
        }

        if ($page->h1_count === 0) {
            $issues[] = [
                'severity' => 'critical',
                'type' => 'missing_h1',
                'message' => 'Page is missing an H1 heading',
                'data' => [],
            ];
        }

        // WARNING issues
        if (!empty($page->title)) {
            $titleLength = mb_strlen($page->title);
            if ($titleLength < 20) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'title_too_short',
                    'message' => "Title is too short ({$titleLength} characters). Recommended: 20-60 characters.",
                    'data' => ['length' => $titleLength],
                ];
            } elseif ($titleLength > 60) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'title_too_long',
                    'message' => "Title is too long ({$titleLength} characters). Recommended: 20-60 characters.",
                    'data' => ['length' => $titleLength],
                ];
            }
        }

        if (empty($page->meta_description)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'meta_description_missing',
                'message' => 'Page is missing a meta description',
                'data' => [],
            ];
        } elseif (!empty($page->meta_description)) {
            $descLength = mb_strlen($page->meta_description);
            if ($descLength < 70) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'meta_description_too_short',
                    'message' => "Meta description is too short ({$descLength} characters). Recommended: 70-160 characters.",
                    'data' => ['length' => $descLength],
                ];
            } elseif ($descLength > 160) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'meta_description_too_long',
                    'message' => "Meta description is too long ({$descLength} characters). Recommended: 70-160 characters.",
                    'data' => ['length' => $descLength],
                ];
            }
        }

        if ($page->h1_count > 1) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'multiple_h1',
                'message' => "Page has {$page->h1_count} H1 headings. Recommended: 1 H1 per page.",
                'data' => ['h1_count' => $page->h1_count],
            ];
        }

        if (empty($page->canonical)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'missing_canonical',
                'message' => 'Page is missing a canonical URL',
                'data' => [],
            ];
        }

        if ($page->word_count > 0 && $page->word_count < 250) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'thin_content',
                'message' => "Page has thin content ({$page->word_count} words). Recommended: 250+ words.",
                'data' => ['word_count' => $page->word_count],
            ];
        }

        // INFO issues
        if (!empty($page->robots_meta) && strpos(strtolower($page->robots_meta), 'nofollow') !== false) {
            $issues[] = [
                'severity' => 'info',
                'type' => 'robots_no_follow',
                'message' => 'Page has nofollow directive in robots meta tag',
                'data' => ['robots_meta' => $page->robots_meta],
            ];
        }

        return $issues;
    }

    /**
     * Check for duplicate titles across pages (called during finalization)
     */
    public static function checkDuplicateTitles(int $auditId): array
    {
        $issues = [];

        // Get all pages with titles, grouped by title
        $pagesByTitle = DomainAuditPage::where('domain_audit_id', $auditId)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->get()
            ->groupBy(function ($page) {
                return mb_strtolower(trim($page->title));
            });

        // Find duplicates
        foreach ($pagesByTitle as $title => $pages) {
            if ($pages->count() > 1) {
                foreach ($pages as $page) {
                    $issues[] = [
                        'domain_audit_page_id' => $page->id,
                        'severity' => 'info',
                        'type' => 'title_duplicate',
                        'message' => "Title is duplicated on {$pages->count()} pages",
                        'data' => [
                            'title' => $page->title,
                            'duplicate_count' => $pages->count(),
                        ],
                    ];
                }
            }
        }

        return $issues;
    }
}



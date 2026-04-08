<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;

class FormsAuthIssueService
{
    /**
     * Replace module issues and return total score_penalty added.
     */
    public function sync(Audit $audit): int
    {
        if (! FormsAuthService::isEnabled($audit)) {
            return 0;
        }

        AuditIssue::where('audit_id', $audit->id)
            ->where('module_key', 'forms_auth_summary')
            ->delete();

        $state = $audit->forms_auth_state ?? [];
        $engine = new RulesEngine;
        $penalty = 0;

        if (empty($state['login_success'])) {
            $penalty += 6;
            $engine->createCustomIssue($audit, [
                'url' => $audit->normalized_url,
                'code' => 'FORMS_AUTH_LOGIN_FAILED',
                'category' => 'technical',
                'module_key' => 'forms_auth_summary',
                'title' => 'Form login did not complete',
                'message' => 'The audit could not establish an authenticated session. Crawl ran without member credentials.',
                'description' => (string) ($state['login_error'] ?? 'Login failed or was not configured.'),
                'impact' => AuditIssue::IMPACT_HIGH,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'severity' => AuditIssue::SEVERITY_CRITICAL,
                'score_penalty' => 6,
                'recommendation' => 'Verify the login URL, selectors, success indicator, and credentials. Ensure the site allows automated browsers.',
                'details_json' => [
                    'issue_kind' => 'login_failed',
                ],
            ]);

            return $penalty;
        }

        $blocked = (int) ($state['pages_blocked_http'] ?? 0);
        if ($blocked > 0) {
            $p = min(8, 2 + $blocked);
            $penalty += $p;
            $engine->createCustomIssue($audit, [
                'url' => $audit->normalized_url,
                'code' => 'FORMS_AUTH_HTTP_BLOCKED',
                'category' => 'technical',
                'module_key' => 'forms_auth_summary',
                'title' => 'Pages blocked after authentication',
                'message' => "{$blocked} URL(s) returned 401/403 even with session cookies.",
                'description' => 'Some protected resources may require stronger auth or different roles.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'severity' => AuditIssue::SEVERITY_WARNING,
                'score_penalty' => $p,
                'recommendation' => 'Review blocked URLs and account permissions.',
                'details_json' => [
                    'issue_kind' => 'http_blocked',
                    'count' => $blocked,
                ],
                'affected_count' => $blocked,
            ]);
        }

        $redir = (int) ($state['pages_login_redirect_suspected'] ?? 0);
        if ($redir > 0) {
            $p = min(6, 1 + $redir);
            $penalty += $p;
            $engine->createCustomIssue($audit, [
                'url' => $audit->normalized_url,
                'code' => 'FORMS_AUTH_LOGIN_REDIRECT',
                'category' => 'technical',
                'module_key' => 'forms_auth_summary',
                'title' => 'Possible login redirects after authentication Crawl',
                'message' => "{$redir} URL(s) still look like login or sign-in pages.",
                'description' => 'Session may be invalid for those paths, or success detection needs tuning.',
                'impact' => AuditIssue::IMPACT_MEDIUM,
                'effort' => AuditIssue::EFFORT_EASY,
                'severity' => AuditIssue::SEVERITY_WARNING,
                'score_penalty' => $p,
                'recommendation' => 'Refine forms-auth success_indicator or verify SPA login flow.',
                'details_json' => [
                    'issue_kind' => 'login_redirect',
                    'count' => $redir,
                ],
                'affected_count' => $redir,
            ]);
        }

        return $penalty;
    }

    public function refreshStateFromPages(Audit $audit): void
    {
        if (! FormsAuthService::isEnabled($audit)) {
            return;
        }

        $pages = AuditPage::where('audit_id', $audit->id)->get();
        $blocked = 0;
        $redir = 0;
        $likely = 0;
        foreach ($pages as $page) {
            $m = $page->auth_crawl_metadata ?? [];
            if (! empty($m['http_auth_blocked'])) {
                $blocked++;
            }
            if (! empty($m['redirected_to_login_suspected'])) {
                $redir++;
            }
            if (! empty($m['likely_authenticated_content'])) {
                $likely++;
            }
        }

        $state = $audit->forms_auth_state ?? [];
        $state['pages_blocked_http'] = $blocked;
        $state['pages_login_redirect_suspected'] = $redir;
        $state['pages_likely_authenticated'] = $likely;
        $state['total_pages_crawled'] = $pages->count();
        $audit->forms_auth_state = $state;
        $audit->save();
    }
}

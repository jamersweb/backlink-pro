<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;

class AuthCrawlMetadataBuilder
{
    /**
     * @return array{
     *     redirected_to_login_suspected: bool,
     *     http_auth_blocked: bool,
     *     likely_authenticated_content: bool
     * }
     */
    public static function build(Audit $audit, int $statusCode, string $finalUrl, ?string $title, ?int $wordCount): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $authEnabled = ! empty($flags['forms_auth_enabled']);
        $state = $audit->forms_auth_state ?? [];
        $loginOk = (bool) ($state['login_success'] ?? false);

        $titleLc = strtolower((string) $title);
        $httpBlocked = in_array($statusCode, [401, 403], true);

        $loginPath = '';
        if ($authEnabled && is_string($audit->forms_auth_login_url) && $audit->forms_auth_login_url !== '') {
            $loginPath = strtolower(rtrim((string) (parse_url($audit->forms_auth_login_url, PHP_URL_PATH) ?? ''), '/') ?: '');
        }
        $finalPath = strtolower(rtrim((string) (parse_url($finalUrl, PHP_URL_PATH) ?? ''), '/') ?: '');

        $samePathAsLogin = $loginPath !== '' && ($finalPath === $loginPath || str_starts_with($finalPath, $loginPath.'/'));
        $titleSuggestsLogin = str_contains($titleLc, 'sign in')
            || str_contains($titleLc, 'log in')
            || str_contains($titleLc, 'login');

        $redirectedSuspected = $authEnabled && $loginOk && $statusCode >= 200 && $statusCode < 400
            && ($samePathAsLogin || ($titleSuggestsLogin && $wordCount !== null && $wordCount < 120));

        $likelyAuthContent = $authEnabled && $loginOk && $statusCode >= 200 && $statusCode < 400
            && ! $httpBlocked
            && ! $redirectedSuspected
            && ($wordCount ?? 0) >= 40;

        return [
            'redirected_to_login_suspected' => $redirectedSuspected,
            'http_auth_blocked' => $httpBlocked,
            'likely_authenticated_content' => $likelyAuthContent,
        ];
    }
}

<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FormsAuthService
{
    public static function isEnabled(Audit $audit): bool
    {
        return ! empty($audit->crawl_module_flags['forms_auth_enabled']);
    }

    public static function maskUsername(?string $username): ?string
    {
        if ($username === null || $username === '') {
            return null;
        }
        $u = trim($username);
        if (str_contains($u, '@')) {
            [$local, $domain] = explode('@', $u, 2);
            $keep = max(1, min(2, strlen($local)));
            $maskedLocal = substr($local, 0, $keep).str_repeat('*', max(0, strlen($local) - $keep));

            return $maskedLocal.'@'.$domain;
        }
        if (strlen($u) <= 2) {
            return str_repeat('*', strlen($u));
        }

        return substr($u, 0, 1).str_repeat('*', strlen($u) - 2).substr($u, -1);
    }

    /**
     * Playwright-shaped cookies for JS render script (decrypted jar).
     *
     * @return list<array<string, mixed>>
     */
    public static function playwrightCookies(Audit $audit): array
    {
        $jar = $audit->forms_auth_cookie_jar;
        if (! is_array($jar)) {
            return [];
        }

        return array_values(array_filter($jar, fn ($c) => is_array($c) && ! empty($c['name'])));
    }

    /**
     * name => value map for Laravel HTTP client (host-relevant cookies only).
     *
     * @return array<string, string>
     */
    public static function simpleCookiesForHost(Audit $audit): array
    {
        $host = strtolower((string) (parse_url($audit->normalized_url, PHP_URL_HOST) ?: ''));
        $host = preg_replace('/^www\./i', '', $host) ?: '';
        $out = [];
        foreach (self::playwrightCookies($audit) as $c) {
            $name = (string) ($c['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $domain = strtolower((string) ($c['domain'] ?? ''));
            $dom = ltrim($domain, '.');
            if ($dom !== '' && $host !== '' && ! str_ends_with($host, $dom) && $dom !== $host) {
                continue;
            }
            $out[$name] = (string) ($c['value'] ?? '');
        }

        return $out;
    }

    /**
     * Run Playwright login once; updates audit cookie jar and forms_auth_state.
     * Does not log credentials.
     */
    public function establishSession(Audit $audit, int $attempt = 1): void
    {
        if (! self::isEnabled($audit)) {
            return;
        }

        $state = $audit->forms_auth_state ?? [];
        if (! empty($state['login_success']) && is_array($audit->forms_auth_cookie_jar) && $audit->forms_auth_cookie_jar !== []) {
            return;
        }

        $loginUrl = trim((string) ($audit->forms_auth_login_url ?? ''));
        $username = $audit->forms_auth_username;
        $password = $audit->forms_auth_password;
        if ($loginUrl === '' || $username === null || $username === '' || $password === null || $password === '') {
            $state['login_success'] = false;
            $state['login_attempted_at'] = now()->toIso8601String();
            $state['login_error'] = 'Missing login URL, username, or password.';
            $state['attempts'] = $attempt;
            $state['username_masked'] = null;
            $audit->forms_auth_state = $state;
            $audit->forms_auth_cookie_jar = null;
            $audit->save();

            return;
        }

        $state['username_masked'] = self::maskUsername(is_string($username) ? $username : null);
        $state['login_attempted_at'] = now()->toIso8601String();
        $state['attempts'] = $attempt;

        $script = (string) config('seo_audit.forms_auth.script_path', base_path('scripts/seo-audit-playwright-forms-auth.mjs'));
        if (! is_readable($script)) {
            $state['login_success'] = false;
            $state['login_error'] = 'Authentication script is not available on this server.';
            $audit->forms_auth_state = $state;
            $audit->save();

            return;
        }

        $payload = [
            'login_url' => $loginUrl,
            'username' => $username,
            'password' => $password,
            'username_selector' => $audit->forms_auth_username_selector,
            'password_selector' => $audit->forms_auth_password_selector,
            'submit_selector' => $audit->forms_auth_submit_selector,
            'success_indicator' => $audit->forms_auth_success_indicator,
            'navigation_timeout_ms' => (int) config('seo_audit.forms_auth.navigation_timeout_ms', 45000),
            'settle_after_login_ms' => (int) config('seo_audit.forms_auth.settle_after_login_ms', 2500),
        ];

        $node = (string) config('seo_audit.forms_auth.node_binary', config('seo_audit.js_render.node_binary', 'node'));
        $timeout = max(45.0, (float) config('seo_audit.forms_auth.process_timeout_seconds', 120));

        $process = new Process([$node, $script], base_path(), null, null, $timeout);
        $process->setInput(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        try {
            $process->run();
        } catch (\Throwable $e) {
            $state['login_success'] = false;
            $state['login_error'] = 'Login process failed to run.';
            $audit->forms_auth_state = $state;
            $audit->forms_auth_cookie_jar = null;
            $audit->save();
            Log::warning('forms_auth: process exception', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);

            return;
        }

        if (! $process->isSuccessful()) {
            $state['login_success'] = false;
            $state['login_error'] = 'Login automation exited with an error.';
            $audit->forms_auth_state = $state;
            $audit->forms_auth_cookie_jar = null;
            $audit->save();
            Log::warning('forms_auth: non-zero exit', [
                'audit_id' => $audit->id,
                'stderr' => substr($process->getErrorOutput(), 0, 500),
            ]);

            return;
        }

        $out = json_decode($process->getOutput(), true);
        if (! is_array($out) || empty($out['ok'])) {
            $state['login_success'] = false;
            $state['login_error'] = is_string($out['error'] ?? null) ? $out['error'] : 'Login failed.';
            $audit->forms_auth_state = $state;
            $audit->forms_auth_cookie_jar = null;
            $audit->save();
            Log::info('forms_auth: login not ok', ['audit_id' => $audit->id]);

            return;
        }

        $cookies = $out['cookies'] ?? [];
        if (! is_array($cookies) || $cookies === []) {
            $state['login_success'] = false;
            $state['login_error'] = 'No session cookies were returned after login.';
            $audit->forms_auth_state = $state;
            $audit->forms_auth_cookie_jar = null;
            $audit->save();

            return;
        }

        $state['login_success'] = true;
        $state['login_error'] = null;
        $state['final_url_after_login'] = is_string($out['final_url'] ?? null) ? $out['final_url'] : null;
        $audit->forms_auth_state = $state;
        $audit->forms_auth_cookie_jar = array_values(array_filter($cookies, fn ($c) => is_array($c) && ! empty($c['name'])));
        $audit->save();

        Log::info('forms_auth: session established', ['audit_id' => $audit->id]);
    }

    public function establishWithRetries(Audit $audit): void
    {
        $max = max(1, (int) config('seo_audit.forms_auth.max_attempts', 2));
        for ($i = 1; $i <= $max; $i++) {
            $this->establishSession($audit, $i);
            $audit->refresh();
            if (! empty(($audit->forms_auth_state ?? [])['login_success'])) {
                return;
            }
            if ($i < $max) {
                usleep(800_000);
            }
        }
    }

    /**
     * Remove secrets after audit completes. Retains forms_auth_state summary for reporting.
     * Caller should set other audit attributes first; this saves the full model in one write.
     */
    public static function scrubSecrets(Audit $audit): void
    {
        $audit->forms_auth_username = null;
        $audit->forms_auth_password = null;
        $audit->forms_auth_cookie_jar = null;
    }
}

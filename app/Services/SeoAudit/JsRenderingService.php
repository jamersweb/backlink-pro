<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class JsRenderingService
{
    public function __construct(
        protected JsRenderingDiffService $diffService
    ) {}

    public function shouldRun(Audit $audit): bool
    {
        if (!config('seo_audit.js_render.enabled_default', true)) {
            return false;
        }

        return !empty($audit->crawl_module_flags['js_rendering_enabled']);
    }

    /**
     * Full crawl: refresh JS diffs for up to max_urls_per_audit pages.
     */
    public function runForAudit(Audit $audit): int
    {
        if (!$this->shouldRun($audit)) {
            return 0;
        }

        AuditIssue::where('audit_id', $audit->id)->where('module_key', 'js_rendering')->delete();

        $pages = AuditPage::where('audit_id', $audit->id)
            ->orderBy('id')
            ->limit((int) config('seo_audit.js_render.max_urls_per_audit', 200))
            ->get();

        return $this->runForPages($audit, $pages, false);
    }

    /**
     * @param  Collection<int, AuditPage>|array<int, AuditPage>  $pages
     * @param  bool  $deleteModuleIssuesForAudit  When false, caller already cleared issues (runForAudit).
     */
    public function runForPages(Audit $audit, Collection|array $pages, bool $deleteModuleIssuesForAudit = true): int
    {
        if (!$this->shouldRun($audit)) {
            return 0;
        }

        $collection = collect($pages);
        if ($collection->isEmpty()) {
            return 0;
        }

        $script = config('seo_audit.js_render.script_path');
        if (!is_string($script) || !is_readable($script)) {
            Log::warning('seo_audit.js_render: script not readable', ['path' => $script]);

            return 0;
        }

        if ($deleteModuleIssuesForAudit) {
            $urls = $collection->pluck('url')->filter()->all();
            AuditIssue::where('audit_id', $audit->id)
                ->where('module_key', 'js_rendering')
                ->whereIn('url', $urls)
                ->delete();
        }

        $internalHost = (string) (parse_url($audit->normalized_url, PHP_URL_HOST) ?: '');
        $chunkSize = max(1, (int) config('seo_audit.js_render.chunk_size', 8));

        foreach ($collection->chunk($chunkSize) as $chunk) {
            /** @var Collection<int, AuditPage> $chunk */
            $urls = $chunk->pluck('url')->filter()->values()->all();
            $map = $this->invokePlaywright($urls, $internalHost, $audit);
            if ($map === null) {
                Log::error('seo_audit.js_render: playwright batch failed');

                continue;
            }

            foreach ($chunk as $page) {
                $row = $map[$page->url] ?? null;
                $snapshot = $this->buildSnapshot($page, is_array($row) ? $row : null);
                $page->js_render_snapshot = $snapshot;
                $page->save();
                $this->diffService->analyzePage($audit, $page);
            }
        }

        return (int) AuditIssue::where('audit_id', $audit->id)->where('module_key', 'js_rendering')->sum('score_penalty');
    }

    /**
     * @return array<string, array>|null
     */
    protected function invokePlaywright(array $urls, string $internalHost, Audit $audit): ?array
    {
        $emitBody = CustomAuditRulesCatalog::auditNeedsRenderedHtmlBody($audit);
        $sessionCookies = FormsAuthService::isEnabled($audit) ? FormsAuthService::playwrightCookies($audit) : [];
        $payload = json_encode([
            'urls' => $urls,
            'internal_host' => $internalHost,
            'navigation_timeout_ms' => (int) config('seo_audit.js_render.navigation_timeout_ms', 30000),
            'settle_after_load_ms' => (int) config('seo_audit.js_render.settle_after_load_ms', 1500),
            'block_heavy_assets' => (bool) config('seo_audit.js_render.block_heavy_assets', true),
            'emit_body_html' => $emitBody,
            'body_html_max_chars' => (int) config('seo_audit.custom_audit.body_html_max_chars', 250000),
            'session_cookies' => $sessionCookies,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $node = (string) config('seo_audit.js_render.node_binary', 'node');
        $script = (string) config('seo_audit.js_render.script_path');
        $timeout = max(30.0, (float) config('seo_audit.js_render.process_timeout_seconds', 180));

        $process = new Process([$node, $script], base_path(), null, null, $timeout);
        $process->setInput($payload);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('seo_audit.js_render: process exception', ['error' => $e->getMessage()]);

            return null;
        }

        if (!$process->isSuccessful()) {
            Log::warning('seo_audit.js_render: non-zero exit', [
                'stderr' => $process->getErrorOutput(),
                'stdout' => substr($process->getOutput(), 0, 800),
            ]);

            return null;
        }

        $out = json_decode($process->getOutput(), true);
        if (!is_array($out) || empty($out['ok'])) {
            Log::warning('seo_audit.js_render: bad json', ['head' => substr($process->getOutput(), 0, 400)]);

            return null;
        }

        $map = [];
        foreach ($out['results'] ?? [] as $row) {
            if (is_array($row) && !empty($row['url'])) {
                $map[(string) $row['url']] = $row;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @return array<string, mixed>
     */
    protected function buildSnapshot(AuditPage $page, ?array $row): array
    {
        $nav = [
            'ok' => $row['ok'] ?? false,
            'error' => $row['error'] ?? null,
            'http_status' => $row['http_status'] ?? null,
            'final_url' => $row['final_url'] ?? null,
            'blocked_resource_aborts' => $row['blocked_resource_aborts'] ?? 0,
            'failed_request_count' => $row['failed_request_count'] ?? 0,
        ];

        return [
            'navigation' => $nav,
            'rendered' => $row['rendered'] ?? null,
            'raw' => [
                'title' => $page->title,
                'meta_description' => $page->meta_description,
                'canonical_url' => $page->canonical_url,
                'robots_meta' => $page->robots_meta,
                'x_robots_tag' => $page->x_robots_tag,
                'visible_text_length' => $page->visible_text_length,
                'word_count' => $page->word_count,
                'internal_links_count' => $page->internal_links_count,
            ],
        ];
    }
}

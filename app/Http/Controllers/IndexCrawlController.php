<?php

namespace App\Http\Controllers;

use App\Jobs\Audits\StartDomainAuditJob;
use App\Models\Domain;
use App\Models\DomainAudit;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\Auth\DomainAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class IndexCrawlController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $domains = $this->accessibleDomains($user->id);

        $selectedDomain = null;
        $selectedDomainId = $request->integer('domain_id');
        if ($selectedDomainId) {
            $selectedDomain = $domains->firstWhere('id', $selectedDomainId);
        } elseif ($domains->count() === 1) {
            $selectedDomain = $domains->first();
        }

        $latestAudit = null;
        $auditHistory = collect();
        $summary = null;
        $counts = null;

        if ($selectedDomain) {
            $auditsQuery = DomainAudit::query()
                ->where('domain_id', $selectedDomain['id'])
                ->where('user_id', $user->id)
                ->latest('id');

            $selectedAuditId = $request->integer('audit_id');
            $latestAudit = $selectedAuditId
                ? (clone $auditsQuery)->where('id', $selectedAuditId)->first()
                : (clone $auditsQuery)->first();

            $auditHistory = (clone $auditsQuery)
                ->limit(10)
                ->get(['id', 'status', 'created_at', 'started_at', 'finished_at', 'health_score', 'summary_json']);

            if ($latestAudit) {
                $summary = $latestAudit->summary_json ?? [];

                $pagesQuery = $latestAudit->pages();
                $pagesCount = (clone $pagesQuery)->count();
                $indexableCount = (clone $pagesQuery)->where('is_indexable', true)->count();
                $nonIndexableCount = max(0, $pagesCount - $indexableCount);
                $noindexCount = (clone $pagesQuery)->where('robots_meta', 'like', '%noindex%')->count();
                $redirectCount = (clone $pagesQuery)->whereBetween('status_code', [300, 399])->count();
                $status404Count = (clone $pagesQuery)->where('status_code', 404)->count();
                $status5xxCount = (clone $pagesQuery)->where('status_code', '>=', 500)->count();

                $counts = [
                    'total_urls_discovered' => $summary['pages_crawled'] ?? $pagesCount,
                    'total_urls_crawled' => $summary['pages_crawled'] ?? $pagesCount,
                    'indexable_urls' => $indexableCount,
                    'non_indexable_urls' => $nonIndexableCount,
                    'blocked_by_robots_urls' => $summary['blocked_by_robots_urls'] ?? 0,
                    'noindex_urls' => $noindexCount,
                    'redirected_urls' => $redirectCount,
                    'status_404_urls' => $status404Count,
                    'status_5xx_urls' => $status5xxCount,
                    'issues_critical' => $summary['issues_critical'] ?? 0,
                    'issues_warning' => $summary['issues_warning'] ?? 0,
                    'issues_info' => $summary['issues_info'] ?? 0,
                ];
            }
        }

        return Inertia::render('IndexCrawl/Index', [
            'domains' => $domains->values(),
            'selectedDomain' => $selectedDomain,
            'latestAudit' => $latestAudit,
            'auditHistory' => $auditHistory,
            'summary' => $summary,
            'counts' => $counts,
            'filters' => [
                'domain_id' => $selectedDomain ? $selectedDomain['id'] : null,
                'audit_id' => $request->integer('audit_id'),
            ],
        ]);
    }

    public function storeDomain(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'url' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'action' => 'nullable|in:save,save_and_run',
            'crawl_limit' => 'nullable|integer|min:1|max:1000',
            'max_depth' => 'nullable|integer|min:0|max:5',
            'include_sitemap' => 'nullable|boolean',
            'include_cwv' => 'nullable|boolean',
        ]);

        $normalized = $this->normalizeDomainUrl($validated['url']);
        if (!$normalized) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.',
            ])->withInput();
        }
        $inputName = isset($validated['name']) ? trim((string) $validated['name']) : '';
        $domainName = $inputName !== '' ? $inputName : $normalized['host'];

        $action = $validated['action'] ?? 'save';
        $teamIds = TeamMember::where('user_id', $user->id)->pluck('team_id');
        $domain = Domain::where('host', $normalized['host'])
            ->where(function ($query) use ($user, $teamIds) {
                $query->where('user_id', $user->id);
                if ($teamIds->isNotEmpty()) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            })
            ->latest('id')
            ->first();

        if ($domain && !app(DomainAccessService::class)->can($user, $domain, 'analyzer.view')) {
            return back()->withErrors([
                'url' => 'You do not have permission to use this domain in Index & Crawl.',
            ])->withInput();
        }

        $createdNew = false;
        if (!$domain) {
            try {
                $quotaService = app(\App\Services\Usage\QuotaService::class);
                $quotaService->assertCan($user, 'domains.max_active', 1);
            } catch (\App\Exceptions\QuotaExceededException $e) {
                return back()->withErrors([
                    'url' => $e->getMessage(),
                ])->withInput();
            }

            $team = $user->primaryTeam();
            if (!$team) {
                $team = Team::create([
                    'owner_user_id' => $user->id,
                    'name' => "{$user->name} Workspace",
                ]);
                TeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => Team::ROLE_OWNER,
                ]);
            }

            try {
                $domain = Domain::create([
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'name' => $domainName,
                    'url' => $normalized['url'],
                    'host' => $normalized['host'],
                    'platform' => Domain::PLATFORM_CUSTOM,
                    'verification_status' => Domain::VERIFICATION_UNVERIFIED,
                    'verification_token' => bin2hex(random_bytes(20)),
                    'default_settings' => [
                        'crawl_limit' => 100,
                        'max_depth' => 3,
                        'include_sitemap' => true,
                        'user_agent' => 'BacklinkProBot/1.0',
                    ],
                    'status' => Domain::STATUS_ACTIVE,
                ]);
            } catch (QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    $domain = Domain::where('user_id', $user->id)
                        ->where('host', $normalized['host'])
                        ->first();
                } else {
                    throw $e;
                }
            }

            if (!$domain) {
                return back()->withErrors([
                    'url' => 'Unable to create domain. Please try again.',
                ])->withInput();
            }

            app(DomainAccessService::class)->ensureDomainAccess($domain);
            if (Schema::hasTable('domain_onboardings')) {
                \App\Models\DomainOnboarding::firstOrCreate(
                    ['domain_id' => $domain->id, 'user_id' => $user->id],
                    [
                        'status' => \App\Models\DomainOnboarding::STATUS_IN_PROGRESS,
                        'steps_json' => [
                            \App\Models\DomainOnboarding::STEP_DOMAIN_ADDED => [
                                'done' => true,
                                'at' => now()->toIso8601String(),
                            ],
                        ],
                    ]
                );
            }
            $createdNew = true;
        }

        if ($action === 'save_and_run') {
            return $this->createAuditAndRedirect($domain, $validated, $createdNew);
        }

        return redirect()->route('index-crawl.index', [
            'domain_id' => $domain->id,
        ])->with(
            'success',
            $createdNew
                ? 'Domain added successfully. You can now start crawl.'
                : 'Domain already exists and has been selected.'
        );
    }

    protected function createAuditAndRedirect(Domain $domain, array $validated, bool $createdNew)
    {
        $user = Auth::user();
        $activeAudit = DomainAudit::where('domain_id', $domain->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [DomainAudit::STATUS_QUEUED, DomainAudit::STATUS_RUNNING])
            ->latest('id')
            ->first();

        if ($activeAudit) {
            return redirect()->route('index-crawl.index', [
                'domain_id' => $domain->id,
                'audit_id' => $activeAudit->id,
            ])->with(
                'warning',
                $createdNew
                    ? 'Domain added. A crawl is already in progress for this domain.'
                    : 'A crawl is already in progress for this domain.'
            );
        }

        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'audits.runs_per_month', 1);
            $quotaService->assertCan($user, 'audits.pages_per_month', (int) ($validated['crawl_limit'] ?? 100));
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return redirect()->route('index-crawl.index', [
                'domain_id' => $domain->id,
            ])->withErrors([
                'new_domain_url' => $e->getMessage(),
            ]);
        }

        $audit = DomainAudit::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'status' => DomainAudit::STATUS_QUEUED,
            'settings_json' => [
                'crawl_limit' => (int) ($validated['crawl_limit'] ?? 100),
                'max_depth' => (int) ($validated['max_depth'] ?? 3),
                'include_sitemap' => (bool) ($validated['include_sitemap'] ?? true),
                'include_cwv' => (bool) ($validated['include_cwv'] ?? false),
            ],
        ]);

        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'audits.runs_per_month', 1, 'month', [
            'audit_id' => $audit->id,
            'domain_id' => $domain->id,
        ]);

        StartDomainAuditJob::dispatch($audit->id)->onQueue('audits');

        return redirect()->route('index-crawl.index', [
            'domain_id' => $domain->id,
            'audit_id' => $audit->id,
        ])->with(
            'success',
            $createdNew ? 'Domain added and crawl started successfully.' : 'Crawl started successfully.'
        );
    }

    protected function normalizeDomainUrl(string $input): ?array
    {
        $url = trim($input);
        if ($url === '') {
            return null;
        }

        $hasScheme = preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $url) === 1;
        if ($hasScheme && !preg_match('/^https?:\/\//i', $url)) {
            return null;
        }
        if (!$hasScheme) {
            $url = 'https://' . $url;
        }

        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parsed['host']);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === '' || !preg_match('/^[a-z0-9.-]+$/', $host)) {
            return null;
        }

        $port = isset($parsed['port']) ? ':' . (int) $parsed['port'] : '';
        $normalizedUrl = "{$scheme}://{$host}{$port}";

        if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return [
            'url' => $normalizedUrl,
            'host' => $host,
        ];
    }

    protected function accessibleDomains(int $userId)
    {
        $accessService = app(DomainAccessService::class);
        $teamIds = TeamMember::where('user_id', $userId)->pluck('team_id');

        $domains = Domain::query()
            ->where(function ($query) use ($userId, $teamIds) {
                $query->where('user_id', $userId);
                if ($teamIds->isNotEmpty()) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            })
            ->latest()
            ->get();

        return $domains
            ->filter(fn (Domain $domain) => $accessService->can(Auth::user(), $domain, 'analyzer.view'))
            ->map(function (Domain $domain) {
                return [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'host' => $domain->host,
                    'url' => $domain->url,
                    'display_label' => trim(($domain->name ? $domain->name . ' — ' : '') . ($domain->host ?: $domain->url)),
                    'default_settings' => $domain->default_settings ?? [],
                ];
            })
            ->values();
    }
}

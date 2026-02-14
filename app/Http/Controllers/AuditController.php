<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\OauthConnection;
use App\Models\GscSite;
use App\Models\Ga4Property;
use App\Models\GscDailyMetric;
use App\Models\GscQueryMetric;
use App\Models\GscPageMetric;
use App\Models\Ga4DailyMetric;
use App\Models\Ga4PageMetric;
use App\Models\Organization;
use App\Models\Lead;
use App\Jobs\RunSeoAuditJob;
use App\Jobs\RunCruxJob;
use App\Services\Billing\PlanLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class AuditController extends Controller
{
    /**
     * List audits for organization
     */
    public function index(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $audits = $organization->audits()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Organizations/Audits/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'audits' => $audits->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'url' => $audit->url,
                    'status' => $audit->status,
                    'overall_score' => $audit->overall_score,
                    'overall_grade' => $audit->overall_grade,
                    'created_at' => $audit->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Show the audit creation form
     */
    public function create()
    {
        return Inertia::render('Audit/Create');
    }

    /**
     * Store a new audit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'lead_email' => ['nullable', 'email', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ]);

        $url = $this->normalizeUrl($validated['url']);
        
        if (!$url) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.'
            ])->withInput();
        }

        // Resolve organization
        $organization = null;
        if ($validated['organization_id'] ?? null) {
            $organization = Organization::find($validated['organization_id']);
        } elseif ($request->attributes->has('currentOrganization')) {
            $organization = $request->attributes->get('currentOrganization');
        } elseif (auth()->check()) {
            $orgUser = auth()->user()->organizationUsers()->first();
            $organization = $orgUser?->organization;
        }

        // Plan enforcement
        $planSnapshot = null;
        if ($organization) {
            $planLimiter = new \App\Services\Billing\PlanLimiter();
            if (!$planLimiter->canCreateAudit($organization)) {
                return back()->withErrors([
                    'url' => 'Daily audit limit reached. Please upgrade your plan or try again tomorrow.'
                ])->withInput();
            }
            
            // Get plan snapshot
            $planLimits = [
                'pages_limit' => $planLimiter->maxPagesLimit($organization),
                'crawl_depth' => $planLimiter->maxDepth($organization),
                'lighthouse_pages' => $planLimiter->maxLighthousePages($organization),
            ];
            $planSnapshot = [
                'plan_key' => $organization->plan_key ?? 'free',
                'limits' => $planLimits,
                'features' => [
                    'pdf_export' => $planLimiter->canExportPdf($organization),
                    'white_label' => $planLimiter->canUseWhiteLabel($organization),
                    'custom_domain' => false, // TODO: Add to PlanLimiter
                ],
            ];
        }

        // Create lead if email provided
        $lead = null;
        if (!empty($validated['lead_email']) && $organization instanceof Organization) {
            $lead = Lead::create([
                'organization_id' => $organization->id,
                'email' => $validated['lead_email'],
                'source' => Lead::SOURCE_PUBLIC_FORM,
                'metadata' => [
                    'ip_hash' => hash('sha256', $request->ip()),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->header('Referer'),
                    'utm' => $request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']),
                ],
            ]);
        }

        // Fast default for public/guest audits (single-page quick audit)
        $defaultPagesLimit = 1;
        $defaultCrawlDepth = 0;
        if ($organization) {
            $defaultPagesLimit = $planSnapshot['limits']['pages_limit'] ?? 25;
            $defaultCrawlDepth = $planSnapshot['limits']['crawl_depth'] ?? 2;
        }

        // Create audit (share_token allows creator to view after redirect from marketing form)
        $audit = Audit::create([
            'user_id' => auth()->id(),
            'organization_id' => $organization?->id,
            'url' => $validated['url'],
            'normalized_url' => $url,
            'status' => Audit::STATUS_QUEUED,
            'mode' => auth()->check() ? Audit::MODE_AUTH : Audit::MODE_GUEST,
            'lead_email' => $validated['lead_email'] ?? null,
            'lead_id' => $lead?->id,
            'plan_snapshot' => $planSnapshot,
            'is_gated' => true,
            'pages_limit' => $defaultPagesLimit,
            'crawl_depth' => $defaultCrawlDepth,
            'share_token' => Str::random(32),
        ]);

        if ($lead) {
            $lead->update(['audit_id' => $audit->id]);
        }

        // Record usage event
        if ($organization) {
            \App\Services\Billing\UsageRecorder::record(
                $organization->id,
                \App\Models\UsageEvent::TYPE_AUDIT_CREATED,
                1,
                $audit->id,
                ['url' => $url]
            );
        }

        // Record usage: audit_created
        if ($organization) {
            \App\Services\Billing\UsageRecorder::record(
                $organization->id,
                \App\Models\UsageEvent::TYPE_AUDIT_CREATED,
                1,
                $audit->id,
                ['url' => $url]
            );
        }

        // Dispatch job
        if ($audit->pages_limit === 1 && $audit->crawl_depth === 0) {
            // Run quick single-page audit inline for faster response
            RunSeoAuditJob::dispatchSync($audit->id);
        } else {
            RunSeoAuditJob::dispatch($audit->id);
        }

        return redirect()->route('audit.show', ['audit' => $audit, 'token' => $audit->share_token]);
    }

    /**
     * Show audit report
     */
    public function show(Audit $audit, Request $request)
    {
        // Check authorization
        $token = $request->query('token');
        $unlockToken = $request->query('unlock_token');
        
        // Check organization access
        $hasOrgAccess = false;
        if (auth()->check() && $audit->organization_id) {
            $org = $audit->organization;
            if ($org && $org->hasUser(auth()->user())) {
                $hasOrgAccess = true;
            }
        }

        // Check unlock token
        $hasUnlockAccess = false;
        if ($unlockToken && $audit->is_gated) {
            $accessToken = \App\Models\ReportAccessToken::verify($unlockToken, $audit);
            if ($accessToken) {
                $hasUnlockAccess = true;
            }
        }

        // Standard access
        $hasStandardAccess = $audit->canBeViewedBy(auth()->user(), $token);

        if (!$hasOrgAccess && !$hasUnlockAccess && !$hasStandardAccess) {
            // If gated, show summary only
            if ($audit->is_gated && $audit->public_summary) {
                return Inertia::render('Audit/ShowGated', [
                    'audit' => [
                        'id' => $audit->id,
                        'url' => $audit->url,
                        'public_summary' => $audit->public_summary,
                        'is_gated' => true,
                    ],
                ]);
            }
            
            abort(403, 'You do not have permission to view this audit.');
        }

        // Load relations
        $page = $audit->pages()->first();
        $issues = $audit->issues()
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('score_penalty', 'desc')
            ->get();
        
        $pages = $audit->pages()
            ->orderBy('url')
            ->get();
        
        $links = $audit->links()
            ->orderBy('type')
            ->orderBy('is_broken', 'desc')
            ->get();
        
        $assets = $audit->assets()
            ->orderBy('size_bytes', 'desc')
            ->get();

        $googleKpis = $this->buildGoogleKpis($audit);
        if (!empty($googleKpis) && $audit->status === Audit::STATUS_COMPLETED) {
            try {
                $kpis = $audit->audit_kpis ?? [];
                $kpis['google'] = $googleKpis;
                $audit->audit_kpis = $kpis;
                $audit->save();
            } catch (\Exception $e) {
                // Ignore persistence errors for KPI cache
            }
        }

        $user = auth()->user();
        $ga4Connected = $user
            ? (bool) ($user->google_access_token || $user->google_refresh_token)
            : false;
        $ga4Integration = [
            'connected' => $ga4Connected,
            'can_connect' => (bool) $user,
            'email' => $user?->google_email,
            'property_id' => $user?->ga4_property_id,
            'missing_refresh_token' => $ga4Connected && empty($user?->google_refresh_token),
        ];

        return Inertia::render('Audit/Show', [
            'audit' => [
                'id' => $audit->id,
                'organization_id' => $audit->organization_id,
                'url' => $audit->url,
                'normalized_url' => $audit->normalized_url,
                'status' => $audit->status,
                'mode' => $audit->mode,
                'overall_score' => $audit->overall_score,
                'overall_grade' => $audit->overall_grade,
                'category_scores' => $audit->category_scores,
                'summary' => $audit->summary,
                'audit_kpis' => $audit->audit_kpis,
                'is_public' => $audit->is_public,
                'share_token' => $audit->share_token,
                'started_at' => $audit->started_at?->toIso8601String(),
                'finished_at' => $audit->finished_at?->toIso8601String(),
                'error' => $audit->error,
                'created_at' => $audit->created_at->toIso8601String(),
                'pages_scanned' => $audit->pages_scanned ?? 0,
                'pages_discovered' => $audit->pages_discovered ?? 0,
                'progress_percent' => $audit->progress_percent ?? 0,
                'crawl_stats' => $audit->crawl_stats ?? [],
                'performance_summary' => $audit->performance_summary ?? null,
            ],
            'google' => $googleKpis,
            'ga4Integration' => $ga4Integration,
            'page' => $page ? [
                'id' => $page->id,
                'url' => $page->url,
                'status_code' => $page->status_code,
                'title' => $page->title,
                'title_len' => $page->title_len,
                'meta_description' => $page->meta_description,
                'meta_len' => $page->meta_len,
                'canonical_url' => $page->canonical_url,
                'robots_meta' => $page->robots_meta,
                'h1_count' => $page->h1_count,
                'h2_count' => $page->h2_count,
                'h3_count' => $page->h3_count,
                'h4_count' => $page->h4_count,
                'h5_count' => $page->h5_count,
                'h6_count' => $page->h6_count,
                'h1_text' => $page->h1_text,
                'lang' => $page->lang,
                'hreflang_present' => $page->hreflang_present,
                'viewport_present' => $page->viewport_present,
                'favicon_present' => $page->favicon_present,
                'analytics_tool' => $page->analytics_tool,
                'iframes_count' => $page->iframes_count,
                'flash_used' => $page->flash_used,
                'social_links' => $page->social_links,
                'x_robots_tag' => $page->x_robots_tag,
                'server_header' => $page->server_header,
                'x_powered_by' => $page->x_powered_by,
                'content_type' => $page->content_type,
                'charset' => $page->charset,
                'content_excerpt' => $page->content_excerpt,
                'word_count' => $page->word_count,
                'images_total' => $page->images_total,
                'images_missing_alt' => $page->images_missing_alt,
                'internal_links_count' => $page->internal_links_count,
                'external_links_count' => $page->external_links_count,
                'og_present' => $page->og_present,
                'twitter_cards_present' => $page->twitter_cards_present,
                'schema_types' => $page->schema_types,
                'html_size_bytes' => $page->html_size_bytes,
                'lighthouse_mobile' => $page->lighthouse_mobile,
                'lighthouse_desktop' => $page->lighthouse_desktop,
                'performance_metrics' => $page->performance_metrics,
                'security_headers' => $page->security_headers,
            ] : null,
            'issues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'code' => $issue->code,
                    'category' => $issue->category,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'impact' => $issue->impact,
                    'effort' => $issue->effort,
                    'score_penalty' => $issue->score_penalty,
                    'recommendation' => $issue->recommendation,
                    'fix_steps' => $issue->fix_steps,
                    'sample_urls' => $issue->sample_urls,
                ];
            }),
            'pages' => $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'url' => $page->url,
                    'status_code' => $page->status_code,
                    'title' => $page->title,
                    'title_len' => $page->title_len,
                'meta_len' => $page->meta_len,
                'word_count' => $page->word_count,
                'images_missing_alt' => $page->images_missing_alt,
                'internal_links_count' => $page->internal_links_count,
                'external_links_count' => $page->external_links_count,
                'h1_count' => $page->h1_count,
                'h2_count' => $page->h2_count,
                'h3_count' => $page->h3_count,
                'performance_metrics' => $page->performance_metrics,
                'security_headers' => $page->security_headers,
                'lighthouse_mobile' => $page->lighthouse_mobile,
                'lighthouse_desktop' => $page->lighthouse_desktop,
                ];
            }),
            'links' => $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'from_url' => $link->from_url,
                    'to_url' => $link->to_url,
                    'type' => $link->type,
                    'status_code' => $link->status_code,
                    'final_url' => $link->final_url,
                    'redirect_hops' => $link->redirect_hops,
                    'is_broken' => $link->is_broken,
                    'error' => $link->error,
                ];
            }),
            'assets' => $assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'audit_page_id' => $asset->audit_page_id,
                    'page_url' => $asset->page_url,
                    'asset_url' => $asset->asset_url,
                    'type' => $asset->type,
                    'size_bytes' => $asset->size_bytes,
                    'status_code' => $asset->status_code,
                    'content_type' => $asset->content_type,
                    'is_third_party' => $asset->is_third_party,
                ];
            }),
            'isOwner' => $audit->isOwnedBy(auth()->user()),
            'shareUrl' => $audit->share_token ? URL::route('audit.show', ['audit' => $audit->id, 'token' => $audit->share_token]) : null,
        ]);
    }

    /**
     * Get audit status (for polling)
     */
    public function status(Audit $audit, Request $request)
    {
        // Check authorization (same rules as show)
        $token = $request->query('token');
        if (!$audit->canBeViewedBy(auth()->user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $page = $audit->pages()->first();

        return response()->json([
            'status' => $audit->status,
            'started_at' => $audit->started_at?->toIso8601String(),
            'finished_at' => $audit->finished_at?->toIso8601String(),
            'overall_score' => $audit->overall_score,
            'overall_grade' => $audit->overall_grade,
            'category_scores' => $audit->category_scores,
            'issues_count' => $audit->issues()->count(),
            'pages_scanned' => $audit->pages_scanned ?? 0,
            'pages_discovered' => $audit->pages_discovered ?? 0,
            'progress_percent' => $audit->progress_percent ?? 0,
            'crawl_stats' => $audit->crawl_stats ?? [],
            'performance_summary' => $audit->performance_summary ?? null,
            'page_metrics' => $page ? [
                'title' => $page->title,
                'title_len' => $page->title_len,
                'meta_description' => $page->meta_description,
                'meta_len' => $page->meta_len,
                'h1_count' => $page->h1_count,
                'h2_count' => $page->h2_count,
                'h3_count' => $page->h3_count,
                'word_count' => $page->word_count,
                'images_total' => $page->images_total,
                'images_missing_alt' => $page->images_missing_alt,
                'internal_links_count' => $page->internal_links_count,
                'external_links_count' => $page->external_links_count,
            ] : null,
            'error' => $audit->error,
        ]);
    }

    /**
     * Get PageSpeed KPIs for audit
     */
    public function pagespeed(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        $hasOrgAccess = false;
        if (auth()->check() && $audit->organization_id) {
            $org = $audit->organization;
            if ($org && $org->hasUser(auth()->user())) {
                $hasOrgAccess = true;
            }
        }

        if (!$hasOrgAccess && !$audit->canBeViewedBy(auth()->user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $google = data_get($audit->audit_kpis, 'google.pagespeed');

        return response()->json([
            'pagespeed' => $google,
        ]);
    }

    /**
     * Run PageSpeed for audit (manual trigger)
     */
    public function runPagespeed(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        $hasOrgAccess = false;
        if (auth()->check() && $audit->organization_id) {
            $org = $audit->organization;
            if ($org && $org->hasUser(auth()->user())) {
                $hasOrgAccess = true;
            }
        }

        if (!$hasOrgAccess && !$audit->canBeViewedBy(auth()->user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $organization = $audit->organization;
        $hasSharedKey = (bool) config('services.google.pagespeed_api_key');
        $hasByokKey = $organization
            && $organization->pagespeed_byok_enabled
            && $organization->pagespeed_last_key_verified_at
            && $organization->pagespeed_api_key_encrypted;

        if (!$hasSharedKey && !$hasByokKey) {
            return response()->json([
                'error' => 'PageSpeed API key not configured.',
            ], 400);
        }

        $shouldRunSync = app()->environment('local') || config('queue.default') === 'sync';
        if ($shouldRunSync) {
            \App\Jobs\RunPageSpeedJob::dispatchSync($audit->id, $audit->normalized_url);
        } else {
            \App\Jobs\RunPageSpeedJob::dispatch($audit->id, $audit->normalized_url)
                ->onQueue('integrations');
        }

        $google = data_get($audit->fresh()->audit_kpis, 'google.pagespeed');

        return response()->json([
            'pagespeed' => $google,
        ]);
    }

    /**
     * Get CrUX KPIs for audit
     */
    public function crux(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        $hasOrgAccess = false;
        if (auth()->check() && $audit->organization_id) {
            $org = $audit->organization;
            if ($org && $org->hasUser(auth()->user())) {
                $hasOrgAccess = true;
            }
        }

        if (!$hasOrgAccess && !$audit->canBeViewedBy(auth()->user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $google = data_get($audit->audit_kpis, 'google.crux');

        return response()->json([
            'crux' => $google,
        ]);
    }

    /**
     * Run CrUX for audit (manual trigger)
     */
    public function runCrux(Audit $audit, Request $request)
    {
        $token = $request->query('token');
        $hasOrgAccess = false;
        if (auth()->check() && $audit->organization_id) {
            $org = $audit->organization;
            if ($org && $org->hasUser(auth()->user())) {
                $hasOrgAccess = true;
            }
        }

        if (!$hasOrgAccess && !$audit->canBeViewedBy(auth()->user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $organization = $audit->organization;
        $hasSharedKey = (bool) config('services.google.crux_api_key');
        $hasByokKey = $organization
            && $organization->crux_byok_enabled
            && $organization->crux_api_key_encrypted;

        if (!$hasSharedKey && !$hasByokKey) {
            return response()->json([
                'error' => 'CrUX API key not configured.',
            ], 400);
        }

        $shouldRunSync = app()->environment('local') || config('queue.default') === 'sync';
        if ($shouldRunSync) {
            RunCruxJob::dispatchSync($audit->id, $audit->normalized_url);
        } else {
            RunCruxJob::dispatch($audit->id, $audit->normalized_url)
                ->onQueue('integrations');
        }

        $google = data_get($audit->fresh()->audit_kpis, 'google.crux');

        return response()->json([
            'crux' => $google,
        ]);
    }

    /**
     * Generate share link for audit
     */
    public function share(Audit $audit)
    {
        // Only owner can share
        if (!$audit->isOwnedBy(auth()->user())) {
            abort(403, 'You do not have permission to share this audit.');
        }

        // Generate share token if not exists
        if (!$audit->share_token) {
            $audit->share_token = Str::random(48);
            $audit->is_public = true;
            $audit->save();
        }

        $shareUrl = URL::route('audit.show', ['audit' => $audit->id, 'token' => $audit->share_token]);

        return response()->json([
            'share_token' => $audit->share_token,
            'share_url' => $shareUrl,
        ]);
    }

    protected function buildGoogleKpis(Audit $audit): array
    {
        $organization = $audit->organization;
        $pagespeed = data_get($audit->audit_kpis, 'google.pagespeed');
        $crux = data_get($audit->audit_kpis, 'google.crux');
        $pagespeedConfigured = (bool) config('services.google.pagespeed_api_key');
        $cruxConfigured = (bool) config('services.google.crux_api_key');

        if ($organization && $organization->pagespeed_byok_enabled && $organization->pagespeed_last_key_verified_at) {
            $pagespeedConfigured = true;
        }
        if ($organization && $organization->crux_byok_enabled && $organization->crux_api_key_encrypted) {
            $cruxConfigured = true;
        }

        if (!$organization) {
            return [
                'connected' => false,
                'pagespeed_configured' => $pagespeedConfigured || (bool) $pagespeed,
                'crux_configured' => $cruxConfigured || (bool) $crux,
                'pagespeed_byok_enabled' => false,
                'crux_byok_enabled' => false,
                'pagespeed' => $pagespeed,
                'crux' => $crux,
            ];
        }

        $connection = OauthConnection::where('organization_id', $organization->id)
            ->where('provider', 'google')
            ->where('status', 'active')
            ->first();

        $connected = (bool) $connection;
        $gscSite = GscSite::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->first();
        $gscSite = $gscSite ?: GscSite::where('organization_id', $organization->id)->first();

        $ga4Property = Ga4Property::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->first();
        $ga4Property = $ga4Property ?: Ga4Property::where('organization_id', $organization->id)->first();

        $now = now();
        $currentStart = $now->copy()->subDays(28)->toDateString();
        $currentEnd = $now->toDateString();
        $previousStart = $now->copy()->subDays(56)->toDateString();
        $previousEnd = $now->copy()->subDays(29)->toDateString();

        $ga4 = null;
        if ($ga4Property) {
            $current = Ga4DailyMetric::where('organization_id', $organization->id)
                ->where('property_id', $ga4Property->property_id)
                ->whereBetween('date', [$currentStart, $currentEnd]);

            $previous = Ga4DailyMetric::where('organization_id', $organization->id)
                ->where('property_id', $ga4Property->property_id)
                ->whereBetween('date', [$previousStart, $previousEnd]);

            $currentTotals = [
                'active_users' => (int) $current->sum('users'),
                'total_users' => (int) $current->sum('users'),
                'sessions' => (int) $current->sum('sessions'),
                'engagement_rate' => round((float) $current->avg('engagement_rate'), 4),
                'avg_engagement_time_sec' => (int) round((float) $current->avg('avg_engagement_time_sec')),
                'page_views' => (int) $current->sum('page_views'),
                'conversions' => (int) $current->sum('conversions'),
                'revenue' => (float) $current->sum('revenue'),
            ];

            $previousTotals = [
                'active_users' => (int) $previous->sum('users'),
                'total_users' => (int) $previous->sum('users'),
                'sessions' => (int) $previous->sum('sessions'),
                'engagement_rate' => round((float) $previous->avg('engagement_rate'), 4),
                'avg_engagement_time_sec' => (int) round((float) $previous->avg('avg_engagement_time_sec')),
                'page_views' => (int) $previous->sum('page_views'),
                'conversions' => (int) $previous->sum('conversions'),
                'revenue' => (float) $previous->sum('revenue'),
            ];

            $latestPageDate = Ga4PageMetric::where('organization_id', $organization->id)
                ->where('property_id', $ga4Property->property_id)
                ->max('date');

            $topPages = [];
            if ($latestPageDate) {
                $topPages = Ga4PageMetric::where('organization_id', $organization->id)
                    ->where('property_id', $ga4Property->property_id)
                    ->where('date', $latestPageDate)
                    ->orderByDesc('views')
                    ->limit(10)
                    ->get()
                    ->map(fn($row) => [
                        'page_path' => $row->page_path,
                        'page_title' => $row->page_title,
                        'views' => $row->views,
                        'active_users' => $row->active_users,
                        'conversions' => $row->conversions,
                    ])
                    ->toArray();
            }

            $ga4 = [
                'property_id' => $ga4Property->property_id,
                'current' => $currentTotals,
                'previous' => $previousTotals,
                'top_pages' => $topPages,
            ];
        }

        $gsc = null;
        if ($gscSite) {
            $current = GscDailyMetric::where('organization_id', $organization->id)
                ->where('site_url', $gscSite->site_url)
                ->whereBetween('date', [$currentStart, $currentEnd]);

            $previous = GscDailyMetric::where('organization_id', $organization->id)
                ->where('site_url', $gscSite->site_url)
                ->whereBetween('date', [$previousStart, $previousEnd]);

            $currentTotals = [
                'clicks' => (int) $current->sum('clicks'),
                'impressions' => (int) $current->sum('impressions'),
                'ctr' => round((float) $current->avg('ctr'), 4),
                'position' => round((float) $current->avg('position'), 2),
            ];

            $previousTotals = [
                'clicks' => (int) $previous->sum('clicks'),
                'impressions' => (int) $previous->sum('impressions'),
                'ctr' => round((float) $previous->avg('ctr'), 4),
                'position' => round((float) $previous->avg('position'), 2),
            ];

            $latestGscDate = GscQueryMetric::where('organization_id', $organization->id)
                ->where('site_url', $gscSite->site_url)
                ->max('date');

            $topQueries = [];
            $topPages = [];
            if ($latestGscDate) {
                $topQueries = GscQueryMetric::where('organization_id', $organization->id)
                    ->where('site_url', $gscSite->site_url)
                    ->where('date', $latestGscDate)
                    ->orderByDesc('clicks')
                    ->limit(10)
                    ->get()
                    ->map(fn($row) => [
                        'query' => $row->query,
                        'clicks' => $row->clicks,
                        'impressions' => $row->impressions,
                        'ctr' => $row->ctr,
                        'position' => $row->position,
                    ])
                    ->toArray();

                $topPages = GscPageMetric::where('organization_id', $organization->id)
                    ->where('site_url', $gscSite->site_url)
                    ->where('date', $latestGscDate)
                    ->orderByDesc('clicks')
                    ->limit(10)
                    ->get()
                    ->map(fn($row) => [
                        'page_url' => $row->page_url,
                        'clicks' => $row->clicks,
                        'impressions' => $row->impressions,
                        'ctr' => $row->ctr,
                        'position' => $row->position,
                    ])
                    ->toArray();
            }

            $gsc = [
                'site_url' => $gscSite->site_url,
                'current' => $currentTotals,
                'previous' => $previousTotals,
                'top_queries' => $topQueries,
                'top_pages' => $topPages,
            ];
        }

        return [
            'connected' => $connected,
            'pagespeed_configured' => $pagespeedConfigured || (bool) $pagespeed,
            'pagespeed_byok_enabled' => (bool) $organization->pagespeed_byok_enabled,
            'crux_configured' => $cruxConfigured || (bool) $crux,
            'crux_byok_enabled' => (bool) $organization->crux_byok_enabled,
            'gsc' => $gsc,
            'ga4' => $ga4,
            'pagespeed' => $pagespeed,
            'crux' => $crux,
        ];
    }

    /**
     * Normalize URL
     * 
     * - Trim whitespace
     * - Add https:// if missing scheme
     * - Normalize host to lowercase
     * - Remove trailing slash for normalized_url
     */
    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }

        // Add scheme if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Normalize host (lowercase)
        $host = strtolower($parsed['host']);

        // Build normalized URL (without trailing slash)
        $normalized = ($parsed['scheme'] ?? 'https') . '://' . $host;
        
        $path = $parsed['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $normalized .= $path;

        if (isset($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }

        return $normalized;
    }
}

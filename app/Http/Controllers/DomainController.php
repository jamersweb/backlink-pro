<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\Auth\DomainAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DomainController extends Controller
{
    /**
     * List all domains accessible to user
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $user = Auth::user();
        $accessService = app(DomainAccessService::class);
        
        // Get domains where user has access
        $query = Domain::where(function($query) use ($user) {
            // Legacy: direct ownership
            $query->where('user_id', $user->id);
            
            // Team-based: domains in teams where user is a member
            $teamIds = TeamMember::where('user_id', $user->id)->pluck('team_id');
            if ($teamIds->isNotEmpty()) {
                $query->orWhereIn('team_id', $teamIds);
            }
        })
            ->withCount('campaigns')
            ->with(['campaigns' => function($query) {
                $query->withCount('backlinks');
            }]);

        // Apply search filter
        if ($q) {
            $query->where(function($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('host', 'like', "%{$q}%");
            });
        }

        // Filter by view permission BEFORE pagination
        $domainIds = [];
        $allDomains = $query->latest()->get();
        foreach ($allDomains as $domain) {
            if ($accessService->can($user, $domain, 'domains.view')) {
                $domainIds[] = $domain->id;
            }
        }

        // Get total count for stats (after permission filtering)
        $totalCount = count($domainIds);

        // Paginate only the domains that passed permission check
        $domains = Domain::whereIn('id', $domainIds)
            ->withCount('campaigns')
            ->with(['campaigns' => function($query) {
                $query->withCount('backlinks');
            }])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function($domain) {
                $domain->total_backlinks = $domain->campaigns->sum('backlinks_count');
                return $domain;
            });

        // Get user's plan limits
        $user = Auth::user();
        $plan = $user->plan;
        
        // Get max_domains from limits_json array
        $maxDomains = $plan ? $plan->getLimit('max_domains') : null;
        
        $stats = [
            'total_domains' => $totalCount,
            'max_domains' => $maxDomains === -1 ? null : $maxDomains,
            'can_add_more' => $plan ? ($maxDomains === -1 || $maxDomains === null || $totalCount < $maxDomains) : true,
        ];

        return Inertia::render('Domains/Index', [
            'domains' => $domains,
            'stats' => $stats,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $platforms = [
            Domain::PLATFORM_WORDPRESS => 'WordPress',
            Domain::PLATFORM_SHOPIFY => 'Shopify',
            Domain::PLATFORM_CUSTOM => 'Custom',
            Domain::PLATFORM_WEBFLOW => 'Webflow',
            Domain::PLATFORM_WIX => 'Wix',
            Domain::PLATFORM_SQUARESPACE => 'Squarespace',
            Domain::PLATFORM_OTHER => 'Other',
        ];

        $defaultSettings = [
            'crawl_limit' => 100,
            'max_depth' => 3,
            'include_sitemap' => true,
            'user_agent' => 'BacklinkProBot/1.0',
        ];

        return Inertia::render('Domains/Create', [
            'platforms' => $platforms,
            'defaultSettings' => $defaultSettings,
        ]);
    }

    /**
     * Store new domain
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'url' => 'required|string|max:255',
            'platform' => 'required|in:wordpress,shopify,custom,webflow,wix,squarespace,other',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Normalize URL and extract host
        $url = $validated['url'];
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.'
            ])->withInput();
        }

        $host = strtolower($parsedUrl['host']);
        // Remove leading www.
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        // Validate normalized URL
        $validated['url'] = $url;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.'
            ])->withInput();
        }

        // Check quota limits
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'domains.max_active', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'url' => $e->getMessage()
            ])->withInput();
        }

        // Get or create user's team
        $team = $user->primaryTeam();
        if (!$team) {
            // Create personal team
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

        // Check for duplicate host in team
        $existingDomain = Domain::where('team_id', $team->id)
            ->where('host', $host)
            ->first();
        
        if ($existingDomain) {
            return back()->withErrors([
                'url' => 'This domain is already added. Each domain can only be added once.'
            ])->withInput();
        }

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(20)); // 40 character token

        $domain = Domain::create([
            'user_id' => Auth::id(),
            'team_id' => $team->id,
            'name' => $validated['name'] ?? $host,
            'url' => $validated['url'],
            'host' => $host,
            'platform' => $validated['platform'],
            'verification_status' => Domain::VERIFICATION_UNVERIFIED,
            'verification_token' => $verificationToken,
            'default_settings' => $validated['default_settings'] ?? [
                'crawl_limit' => 100,
                'max_depth' => 3,
                'include_sitemap' => true,
                'user_agent' => 'BacklinkProBot/1.0',
            ],
            'status' => $validated['status'] ?? Domain::STATUS_ACTIVE,
        ]);

        // Ensure domain access record
        $accessService = app(DomainAccessService::class);
        $accessService->ensureDomainAccess($domain);

        // Create onboarding record
        \App\Models\DomainOnboarding::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'status' => \App\Models\DomainOnboarding::STATUS_IN_PROGRESS,
            'steps_json' => [
                \App\Models\DomainOnboarding::STEP_DOMAIN_ADDED => [
                    'done' => true,
                    'at' => now()->toIso8601String(),
                ],
            ],
        ]);

        // Redirect to setup wizard
        return redirect()->route('domains.setup.show', $domain->id)
            ->with('success', 'Domain created successfully. Let\'s set it up!');
    }

    /**
     * Show domain details
     */
    public function show(Request $request, $id)
    {
        $domain = Domain::withCount('campaigns')->findOrFail($id);
        $user = Auth::user();
        
        // Authorize view access
        Gate::authorize('domain.view', $domain);
        
        $accessService = app(DomainAccessService::class);
        $abilities = $accessService->getAbilities($user, $domain);

        // Calculate total backlinks
        $domain->total_backlinks = $domain->campaigns()
            ->withCount('backlinks')
            ->get()
            ->sum('backlinks_count');

        $tab = $request->query('tab', 'overview');
        $validTabs = ['overview', 'analyzer', 'integrations', 'backlinks', 'meta'];
        if (!in_array($tab, $validTabs)) {
            $tab = 'overview';
        }

        $platforms = [
            Domain::PLATFORM_WORDPRESS => 'WordPress',
            Domain::PLATFORM_SHOPIFY => 'Shopify',
            Domain::PLATFORM_CUSTOM => 'Custom',
            Domain::PLATFORM_WEBFLOW => 'Webflow',
            Domain::PLATFORM_WIX => 'Wix',
            Domain::PLATFORM_SQUARESPACE => 'Squarespace',
            Domain::PLATFORM_OTHER => 'Other',
        ];

        // Get recent activity logs for this domain
        $activityLogs = \App\Models\SystemActivityLog::where('domain_id', $domain->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Domains/Show', [
            'domain' => $domain,
            'tab' => $tab,
            'platforms' => $platforms,
            'activityLogs' => $activityLogs,
            'abilities' => $abilities,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $domain = Domain::findOrFail($id);
        Gate::authorize('domain.manage', $domain);

        $platforms = [
            Domain::PLATFORM_WORDPRESS => 'WordPress',
            Domain::PLATFORM_SHOPIFY => 'Shopify',
            Domain::PLATFORM_CUSTOM => 'Custom',
            Domain::PLATFORM_WEBFLOW => 'Webflow',
            Domain::PLATFORM_WIX => 'Wix',
            Domain::PLATFORM_SQUARESPACE => 'Squarespace',
            Domain::PLATFORM_OTHER => 'Other',
        ];

        return Inertia::render('Domains/Edit', [
            'domain' => $domain,
            'platforms' => $platforms,
        ]);
    }

    /**
     * Update domain
     */
    public function update(Request $request, $id)
    {
        $domain = Domain::findOrFail($id);
        Gate::authorize('domain.manage', $domain);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'url' => 'required|string|max:255',
            'platform' => 'required|in:wordpress,shopify,custom,webflow,wix,squarespace,other',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Normalize URL and extract host
        $url = $validated['url'];
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.'
            ])->withInput();
        }

        $host = strtolower($parsedUrl['host']);
        // Remove leading www.
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        // Validate normalized URL
        $validated['url'] = $url;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return back()->withErrors([
                'url' => 'Invalid URL format. Please enter a valid website URL.'
            ])->withInput();
        }

        // Check if URL/host changed
        $urlChanged = $domain->url !== $url || $domain->host !== $host;
        
        // Check for duplicate host if host changed
        if ($urlChanged && $host !== $domain->host) {
            $existingDomain = Domain::where('team_id', $domain->team_id)
                ->where('host', $host)
                ->where('id', '!=', $domain->id)
                ->first();
            
            if ($existingDomain) {
                return back()->withErrors([
                    'url' => 'This domain is already added. Each domain can only be added once.'
                ])->withInput();
            }
        }

        // Update fields
        $updateData = [
            'name' => $validated['name'] ?? $host,
            'url' => $validated['url'],
            'host' => $host,
            'platform' => $validated['platform'],
            'default_settings' => $validated['default_settings'] ?? $domain->default_settings,
            'status' => $validated['status'] ?? $domain->status,
        ];

        // If URL changed, reset verification
        if ($urlChanged) {
            $updateData['verification_status'] = Domain::VERIFICATION_UNVERIFIED;
            $updateData['verification_method'] = null;
            $updateData['verification_token'] = bin2hex(random_bytes(20));
            $updateData['verified_at'] = null;
        }

        $domain->update($updateData);

        return redirect()->route('domains.show', $domain->id)
            ->with('success', 'Domain updated successfully');
    }

    /**
     * Delete domain
     */
    public function destroy($id)
    {
        $domain = Domain::findOrFail($id);
        Gate::authorize('domain.manage', $domain);

        $domain->delete();

        return redirect()->route('domains.index')
            ->with('success', 'Domain deleted successfully');
    }
}


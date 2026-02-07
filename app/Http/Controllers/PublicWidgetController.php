<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Audit;
use App\Models\Lead;
use App\Models\Organization;
use App\Jobs\RunSeoAuditJob;
use App\Services\SeoAudit\PlanEnforcementService;
use App\Services\SeoAudit\UrlNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicWidgetController extends Controller
{
    /**
     * Create audit via widget API
     */
    public function createAudit(Request $request)
    {
        // Verify API key
        $apiKey = $request->header('X-API-KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $key = ApiKey::verify($apiKey);
        if (!$key || !$key->is_active) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        if (!$key->hasScope('audit:create')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Rate limiting per API key
        $rateLimitKey = "widget:create:key:{$key->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 100)) { // 100 per hour
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }

        // Rate limiting per IP
        $ipHash = hash('sha256', $request->ip());
        $ipRateLimitKey = "widget:create:ip:{$ipHash}";
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 20)) { // 20 per hour per IP
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }

        // Validate input
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'utm' => ['nullable', 'array'],
            'referrer' => ['nullable', 'string'],
        ]);

        $organization = $key->organization;

        // Plan enforcement
        $planService = new PlanEnforcementService();
        if (!$planService->canCreateAudit($organization)) {
            return response()->json(['error' => 'Daily audit limit reached'], 429);
        }

        $planSnapshot = $planService->getPlanSnapshot($organization);
        $planService->recordAuditCreation($organization);

        // Normalize URL
        $url = $this->normalizeUrl($validated['url']);
        if (!$url) {
            return response()->json(['error' => 'Invalid URL format'], 422);
        }

        // Create lead
        $lead = Lead::create([
            'organization_id' => $organization->id,
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'source' => Lead::SOURCE_WIDGET,
            'metadata' => [
                'ip_hash' => $ipHash,
                'user_agent' => $request->userAgent(),
                'referrer' => $validated['referrer'] ?? $request->header('Referer'),
                'utm' => $validated['utm'] ?? [],
            ],
        ]);

        // Create audit
        $audit = Audit::create([
            'organization_id' => $organization->id,
            'url' => $validated['url'],
            'normalized_url' => $url,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_GUEST,
            'lead_email' => $validated['email'],
            'lead_id' => $lead->id,
            'plan_snapshot' => $planSnapshot,
            'is_gated' => true,
            'pages_limit' => $planSnapshot['limits']['pages_limit'] ?? 25,
            'crawl_depth' => $planSnapshot['limits']['crawl_depth'] ?? 2,
        ]);

        $lead->update(['audit_id' => $audit->id]);

        // Generate share token
        $audit->share_token = Str::random(48);
        $audit->is_public = true;
        $audit->save();

        // Record usage: widget_audit_created
        \App\Services\Billing\UsageRecorder::record(
            $organization->id,
            \App\Models\UsageEvent::TYPE_WIDGET_AUDIT_CREATED,
            1,
            $audit->id,
            ['url' => $url, 'source' => 'widget']
        );

        // Dispatch job
        RunSeoAuditJob::dispatch($audit->id);

        // Record rate limits
        RateLimiter::hit($rateLimitKey, 3600);
        RateLimiter::hit($ipRateLimitKey, 3600);

        return response()->json([
            'audit_id' => $audit->id,
            'report_url' => route('public.report.show', $audit->share_token),
            'status' => 'queued',
        ], 201);
    }

    /**
     * Normalize URL
     */
    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower($parsed['host']);
        $path = $parsed['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return ($parsed['scheme'] ?? 'https') . '://' . $host . $path;
    }
}

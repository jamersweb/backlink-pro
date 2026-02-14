<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainBacklink;
use App\Models\DomainBacklinkRun;
use App\Models\BacklinkTag;
use App\Models\BacklinkRefDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BacklinkQualityController extends Controller
{
    /**
     * Show backlink quality dashboard for a domain (latest run)
     */
    public function index(Request $request, Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $latestRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        $filters = [
            'action_status' => $request->query('action_status'),
            'risk_min' => $request->query('risk_min'),
            'risk_max' => $request->query('risk_max'),
            'tag_id' => $request->query('tag_id'),
            'search' => $request->query('search'),
        ];

        $backlinks = collect([]);
        $stats = [
            'total' => 0,
            'toxic' => 0,
            'review' => 0,
            'keep' => 0,
            'disavow' => 0,
        ];

        if ($latestRun) {
            $query = DomainBacklink::where('run_id', $latestRun->id)
                ->with(['refDomain', 'tags'])
                ->orderByDesc('risk_score');

            if ($filters['action_status']) {
                $query->where('action_status', $filters['action_status']);
            }
            if ($filters['risk_min'] !== null && $filters['risk_min'] !== '') {
                $query->where('risk_score', '>=', (int) $filters['risk_min']);
            }
            if ($filters['risk_max'] !== null && $filters['risk_max'] !== '') {
                $query->where('risk_score', '<=', (int) $filters['risk_max']);
            }
            if ($filters['tag_id']) {
                $query->whereHas('tags', function ($q) use ($filters) {
                    $q->where('backlink_tags.id', $filters['tag_id']);
                });
            }
            if ($filters['search']) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('source_domain', 'like', "%{$search}%")
                        ->orWhere('source_url', 'like', "%{$search}%")
                        ->orWhere('anchor', 'like', "%{$search}%");
                });
            }

            $backlinks = $query->paginate(25)->withQueryString();

            $statsQuery = DomainBacklink::where('run_id', $latestRun->id);
            $stats = [
                'total' => $statsQuery->count(),
                'toxic' => (clone $statsQuery)->where('risk_score', '>=', 80)->count(),
                'review' => (clone $statsQuery)->whereBetween('risk_score', [55, 79])->count(),
                'keep' => (clone $statsQuery)->where('action_status', DomainBacklink::ACTION_KEEP)->count(),
                'disavow' => (clone $statsQuery)->where('action_status', DomainBacklink::ACTION_DISAVOW)->count(),
            ];
        }

        $tags = BacklinkTag::where('domain_id', $domain->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('BacklinksQuality/Index', [
            'domain' => $domain,
            'latestRun' => $latestRun,
            'backlinks' => $backlinks,
            'tags' => $tags,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Update backlink action status
     */
    public function updateAction(Request $request, Domain $domain, DomainBacklink $backlink)
    {
        if ($domain->user_id !== Auth::id() || $backlink->run->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action_status' => 'required|in:keep,review,remove,disavow',
        ]);

        $backlink->update([
            'action_status' => $validated['action_status'],
        ]);

        return back()->with('success', 'Action updated.');
    }

    /**
     * Update backlink tags
     */
    public function updateTags(Request $request, Domain $domain, DomainBacklink $backlink)
    {
        if ($domain->user_id !== Auth::id() || $backlink->run->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer',
        ]);

        $tagIds = $validated['tag_ids'] ?? [];
        $allowedTagIds = BacklinkTag::where('domain_id', $domain->id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->toArray();

        $backlink->tags()->sync($allowedTagIds);

        return back()->with('success', 'Tags updated.');
    }

    /**
     * Update ref domain status
     */
    public function updateRefDomainStatus(Request $request, Domain $domain, BacklinkRefDomain $refDomain)
    {
        if ($domain->user_id !== Auth::id() || $refDomain->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:ok,review,toxic,disavowed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $refDomain->update($validated);

        return back()->with('success', 'Ref domain updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\BacklinkCampaign;
use App\Models\BacklinkProspect;
use App\Models\Organization;
use App\Services\Backlinks\BacklinkStrategyEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BacklinkCampaignController extends Controller
{
    /**
     * List campaigns
     */
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $campaigns = BacklinkCampaign::where('organization_id', $organization->id)
            ->with(['audit', 'prospects'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Backlinks/Campaigns/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'target_domain' => $campaign->target_domain,
                    'status' => $campaign->status,
                    'prospects_count' => $campaign->prospects->count(),
                    'created_at' => $campaign->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create campaign from audit
     */
    public function create(Request $request, Organization $organization, Audit $audit)
    {
        $this->authorize('manage', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_domain' => ['required', 'string', 'max:255'],
            'goals' => ['nullable', 'array'],
        ]);

        $campaign = BacklinkCampaign::create([
            'organization_id' => $organization->id,
            'audit_id' => $audit->id,
            'name' => $validated['name'],
            'target_domain' => $validated['target_domain'],
            'goals' => $validated['goals'] ?? [],
            'status' => BacklinkCampaign::STATUS_DRAFT,
        ]);

        // Generate strategy
        $strategyEngine = new BacklinkStrategyEngine();
        $strategy = $strategyEngine->generateStrategy($campaign, $audit);

        // Create prospects
        foreach ($strategy['prospects'] as $prospectData) {
            BacklinkProspect::create([
                'campaign_id' => $campaign->id,
                'prospect_url' => $prospectData['url'],
                'domain' => parse_url($prospectData['url'], PHP_URL_HOST),
                'type' => $prospectData['type'],
                'relevance_score' => $prospectData['relevance_score'] ?? 0,
                'authority_score' => $prospectData['authority_score'] ?? 0,
                'risk_score' => $prospectData['risk_score'] ?? 0,
                'contact_email' => $prospectData['contact_email'] ?? null,
            ]);
        }

        return redirect()->route('backlinks.campaigns.show', [
            'organization' => $organization->id,
            'campaign' => $campaign->id,
        ])->with('success', 'Campaign created.');
    }

    /**
     * Show campaign
     */
    public function show(Organization $organization, BacklinkCampaign $campaign)
    {
        $this->authorize('view', $organization);

        if ($campaign->organization_id !== $organization->id) {
            abort(403);
        }

        $campaign->load(['prospects.outreachMessages', 'verifications']);

        return Inertia::render('Backlinks/Campaigns/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'target_domain' => $campaign->target_domain,
                'status' => $campaign->status,
                'goals' => $campaign->goals,
                'prospects' => $campaign->prospects->map(function ($prospect) {
                    return [
                        'id' => $prospect->id,
                        'prospect_url' => $prospect->prospect_url,
                        'domain' => $prospect->domain,
                        'type' => $prospect->type,
                        'relevance_score' => $prospect->relevance_score,
                        'authority_score' => $prospect->authority_score,
                        'risk_score' => $prospect->risk_score,
                        'outreach_status' => $prospect->outreach_status,
                        'contact_email' => $prospect->contact_email,
                    ];
                }),
            ],
        ]);
    }
}

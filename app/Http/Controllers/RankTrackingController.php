<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\RankProject;
use App\Models\RankKeyword;
use App\Jobs\RunRankChecksJob;
use App\Services\Billing\PlanLimiter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RankTrackingController extends Controller
{
    /**
     * List rank projects
     */
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $projects = RankProject::where('organization_id', $organization->id)
            ->withCount('keywords')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('SEO/Rankings', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'target_domain' => $project->target_domain,
                    'status' => $project->status,
                    'keywords_count' => $project->keywords_count,
                ];
            }),
        ]);
    }

    /**
     * Create rank project
     */
    public function createProject(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_domain' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'language_code' => ['required', 'string', 'max:5'],
        ]);

        $project = RankProject::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'target_domain' => $validated['target_domain'],
            'country_code' => $validated['country_code'],
            'language_code' => $validated['language_code'],
            'status' => RankProject::STATUS_ACTIVE,
        ]);

        return redirect()->back()->with('success', 'Rank project created.');
    }

    /**
     * Add keywords to project
     */
    public function addKeywords(Request $request, Organization $organization, RankProject $project)
    {
        $this->authorize('manage', $organization);

        if ($project->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'keywords' => ['required', 'array', 'min:1'],
            'keywords.*' => ['string', 'max:255'],
            'device' => ['required', 'string', 'in:mobile,desktop'],
            'location' => ['nullable', 'string'],
        ]);

        // Check plan limits
        $planLimiter = app(PlanLimiter::class);
        $currentCount = $project->keywords()->count();
        $planLimit = $planLimiter->maxKeywords($organization);

        if ($currentCount + count($validated['keywords']) > $planLimit) {
            return redirect()->back()->with('error', "Plan limit exceeded. Maximum {$planLimit} keywords allowed.");
        }

        foreach ($validated['keywords'] as $keyword) {
            RankKeyword::create([
                'rank_project_id' => $project->id,
                'keyword' => $keyword,
                'device' => $validated['device'],
                'location' => $validated['location'] ?? null,
                'is_active' => true,
            ]);
        }

        return redirect()->back()->with('success', count($validated['keywords']) . ' keywords added.');
    }


    /**
     * Show project keywords
     */
    public function showProject(Organization $organization, RankProject $project)
    {
        $this->authorize('view', $organization);

        if ($project->organization_id !== $organization->id) {
            abort(403);
        }

        $keywords = $project->keywords()
            ->with(['results' => function ($query) {
                $query->orderBy('fetched_at', 'desc')->limit(2);
            }])
            ->get();

        return Inertia::render('SEO/Rankings/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'target_domain' => $project->target_domain,
                'country_code' => $project->country_code,
                'language_code' => $project->language_code,
            ],
            'keywords' => $keywords->map(function ($keyword) {
                $results = $keyword->results;
                $latest = $results->first();
                $previous = $results->count() > 1 ? $results->get(1) : null;

                return [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'device' => $keyword->device,
                    'latest_result' => $latest ? [
                        'position' => $latest->position,
                        'url' => $latest->found_url,
                        'last_checked' => $latest->fetched_at?->toIso8601String(),
                    ] : null,
                    'previous_result' => $previous ? [
                        'position' => $previous->position,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Run rank checks for project
     */
    public function runChecks(Organization $organization, RankProject $project)
    {
        $this->authorize('manage', $organization);

        if ($project->organization_id !== $organization->id) {
            abort(403);
        }

        RunRankChecksJob::dispatch($project->id);

        return redirect()->back()->with('success', 'Rank checks started.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\KeywordResearchRun;
use App\Models\KeywordResearchItem;
use App\Models\RankProject;
use App\Models\RankKeyword;
use App\Jobs\RunRankChecksJob;
use App\Services\Billing\PlanLimiter;
use App\Services\KeywordResearch\KeywordResearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RankTrackingController extends Controller
{
    /**
     * List rank projects
     */
    public function index(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $user = $request->user();
        $selectedRunId = $request->integer('run');

        $recentRuns = KeywordResearchRun::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        $selectedRun = null;
        if ($selectedRunId) {
            $selectedRun = KeywordResearchRun::query()
                ->where('id', $selectedRunId)
                ->where('user_id', $user->id)
                ->with('items')
                ->firstOrFail();
        } elseif ($recentRuns->isNotEmpty()) {
            $selectedRun = KeywordResearchRun::query()
                ->where('id', $recentRuns->first()->id)
                ->where('user_id', $user->id)
                ->with('items')
                ->first();
        }

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('SEO/Rankings', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                ];
            }),
            'recentRuns' => $recentRuns->map(function ($run) {
                return [
                    'id' => $run->id,
                    'input_type' => $run->input_type,
                    'seed_preview' => \Illuminate\Support\Str::limit($run->seed_query ?: ($run->context_text ?? ''), 70),
                    'result_count' => $run->result_count,
                    'created_at' => $run->created_at?->toIso8601String(),
                ];
            }),
            'selectedRun' => $selectedRun ? [
                'id' => $selectedRun->id,
                'input_type' => $selectedRun->input_type,
                'seed_query' => $selectedRun->seed_query,
                'seed_url' => $selectedRun->seed_url,
                'context_text' => $selectedRun->context_text,
                'summary_text' => $selectedRun->summary_text,
                'result_count' => $selectedRun->result_count,
                'locale_country' => $selectedRun->locale_country,
                'locale_language' => $selectedRun->locale_language,
                'created_at' => $selectedRun->created_at?->toIso8601String(),
                'stats' => $this->buildRunStats($selectedRun),
                'items' => $selectedRun->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'keyword' => $item->keyword,
                        'intent' => $item->intent,
                        'funnel_stage' => $item->funnel_stage,
                        'cluster_name' => $item->cluster_name,
                        'recommended_content_type' => $item->recommended_content_type,
                        'confidence_score' => $item->confidence_score,
                        'business_relevance_score' => $item->business_relevance_score,
                        'ai_reason' => $item->ai_reason,
                        'is_saved' => (bool) $item->is_saved,
                    ];
                }),
            ] : null,
        ]);
    }

    public function store(Request $request, Organization $organization, KeywordResearchService $keywordResearchService)
    {
        $this->authorize('view', $organization);

        $validated = $request->validate([
            'input_type' => ['required', 'in:keyword,product,page'],
            'input_text' => ['nullable', 'string', 'max:4000'],
            'page_url' => ['nullable', 'url', 'max:2048'],
            'locale_country' => ['nullable', 'string', 'max:10'],
            'locale_language' => ['nullable', 'string', 'max:10'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $inputType = $validated['input_type'];
        $inputText = trim((string) ($validated['input_text'] ?? ''));
        $pageUrl = trim((string) ($validated['page_url'] ?? ''));

        if (($inputType === 'keyword' || $inputType === 'product') && $inputText === '') {
            return back()->withErrors([
                'input_text' => 'Input text is required for this mode.',
            ])->withInput();
        }

        if ($inputType === 'page' && $inputText === '' && $pageUrl === '') {
            return back()->withErrors([
                'input_text' => 'Provide either a page URL or a page description.',
            ])->withInput();
        }

        if (!empty($validated['project_id'])) {
            $projectBelongsToUser = Project::query()
                ->where('id', $validated['project_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$projectBelongsToUser) {
                return back()->withErrors([
                    'project_id' => 'Selected project is not available for this user.',
                ])->withInput();
            }
        }

        $run = $keywordResearchService->generateAndStore($request->user(), $organization, $validated);

        return redirect()->route('orgs.seo.rankings.index', [
            'organization' => $organization->id,
            'run' => $run->id,
        ])->with('success', 'Keyword research generated successfully.');
    }

    public function toggleSave(Request $request, Organization $organization, KeywordResearchItem $item)
    {
        $this->authorize('view', $organization);

        $item->load('run');
        if ($item->run->user_id !== $request->user()->id) {
            abort(403);
        }

        $item->update([
            'is_saved' => !$item->is_saved,
        ]);

        return redirect()->route('orgs.seo.rankings.index', [
            'organization' => $organization->id,
            'run' => $item->run_id,
        ])->with('success', $item->is_saved ? 'Keyword saved.' : 'Keyword unsaved.');
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

    protected function buildRunStats(KeywordResearchRun $run): array
    {
        $items = $run->items;

        return [
            'total_keywords' => $items->count(),
            'unique_clusters' => $items->pluck('cluster_name')->filter()->unique()->count(),
            'informational_keywords' => $items->where('intent', 'informational')->count(),
            'commercial_transactional_keywords' => $items->whereIn('intent', ['commercial', 'transactional'])->count(),
            'saved_keywords' => $items->where('is_saved', true)->count(),
        ];
    }
}

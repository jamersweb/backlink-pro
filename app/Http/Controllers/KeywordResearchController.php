<?php

namespace App\Http\Controllers;

use App\Models\KeywordResearchItem;
use App\Models\KeywordResearchRun;
use App\Models\Organization;
use App\Models\RankKeyword;
use App\Models\RankProject;
use App\Models\User;
use App\Services\Billing\PlanLimiter;
use App\Services\KeywordResearch\KeywordResearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class KeywordResearchController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->keywordStorageReady()) {
            return Inertia::render('SEO/Rankings', [
                'organization' => [
                    'id' => null,
                    'name' => 'Keyword Workspace',
                ],
                'rankProjects' => [],
                'recentRuns' => [],
                'selectedRun' => null,
                'storageReady' => false,
            ]);
        }

        $user = $request->user();
        $organization = $this->resolveUserOrganization($user);
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

        if ($selectedRun) {
            app(KeywordResearchService::class)->enrichMissingMetricsForRun($selectedRun);
            $selectedRun->refresh()->load('items');
        }

        $rankProjects = collect();
        if ($organization) {
            $rankProjects = RankProject::query()
                ->where('organization_id', $organization->id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return Inertia::render('SEO/Rankings', [
            'organization' => [
                'id' => $organization?->id,
                'name' => $organization?->name ?? 'Keyword Workspace',
            ],
            'rankProjects' => $rankProjects->map(function ($project) {
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
                        'keyword_density_pct' => $item->keyword_density_pct,
                        'keyword_traffic' => $item->keyword_traffic,
                        'ai_reason' => $item->ai_reason,
                        'is_saved' => (bool) $item->is_saved,
                    ];
                }),
            ] : null,
            'storageReady' => true,
        ]);
    }

    public function store(Request $request, KeywordResearchService $keywordResearchService)
    {
        if (!$this->keywordStorageReady()) {
            return back()->with('error', 'Keyword storage tables are missing. Run migrations and try again.');
        }

        $user = $request->user();
        $organization = $this->resolveUserOrganization($user);

        $validated = $request->validate([
            'input_type' => ['required', 'in:keyword,product,page'],
            'input_text' => ['nullable', 'string', 'max:4000'],
            'page_url' => ['nullable', 'url', 'max:2048'],
            'locale_country' => ['nullable', 'string', 'max:100'],
            'locale_language' => ['nullable', 'string', 'max:20'],
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

        $run = $keywordResearchService->generateAndStore($user, $organization, $validated);

        return redirect()->route('keyword-research.index', [
            'run' => $run->id,
        ])->with('success', 'Keyword research generated successfully.');
    }

    public function toggleSave(Request $request, KeywordResearchItem $item)
    {
        if (!$this->keywordStorageReady()) {
            return back()->with('error', 'Keyword storage tables are missing. Run migrations and try again.');
        }

        $item->load('run');
        if ($item->run->user_id !== $request->user()->id) {
            abort(403);
        }

        $item->update([
            'is_saved' => !$item->is_saved,
        ]);

        return redirect()->route('keyword-research.index', [
            'run' => $item->run_id,
        ])->with('success', $item->is_saved ? 'Keyword saved.' : 'Keyword unsaved.');
    }

    public function addToTracking(Request $request, RankProject $project)
    {
        if (!$this->keywordStorageReady()) {
            return back()->with('error', 'Keyword storage tables are missing. Run migrations and try again.');
        }

        $user = $request->user();
        if (!$this->userCanAccessRankProject($user, $project)) {
            abort(403);
        }

        $validated = $request->validate([
            'keywords' => ['required', 'array', 'min:1'],
            'keywords.*' => ['string', 'max:255'],
            'device' => ['required', 'string', 'in:mobile,desktop'],
            'location' => ['nullable', 'string'],
        ]);

        $organization = Organization::find($project->organization_id);
        if (!$organization) {
            return back()->with('error', 'Organization not found for this rank project.');
        }

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

        return redirect()->back()->with('success', count($validated['keywords']) . ' keywords added to rank tracking.');
    }

    protected function resolveUserOrganization(User $user): ?Organization
    {
        $organizationId = $user->organizationUsers()
            ->select('organization_id')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->value('organization_id');

        if (!$organizationId) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

    protected function userCanAccessRankProject(User $user, RankProject $project): bool
    {
        return Organization::query()
            ->where('id', $project->organization_id)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->exists();
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

    protected function keywordStorageReady(): bool
    {
        return Schema::hasTable('keyword_research_runs') && Schema::hasTable('keyword_research_items');
    }
}

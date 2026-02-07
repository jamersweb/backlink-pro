<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\CompetitorRun;
use App\Models\CompetitorSnapshot;
use App\Models\Organization;
use App\Jobs\RunCompetitorBenchmarkJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompetitorController extends Controller
{
    /**
     * Show competitor benchmarking interface
     */
    public function index(Organization $organization, Audit $audit)
    {
        $this->authorize('view', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $runs = CompetitorRun::where('audit_id', $audit->id)
            ->with('snapshots')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Audits/Competitors', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'audit' => [
                'id' => $audit->id,
                'url' => $audit->url,
            ],
            'runs' => $runs->map(function ($run) {
                return [
                    'id' => $run->id,
                    'keywords' => $run->keywords,
                    'country' => $run->country,
                    'status' => $run->status,
                    'snapshots_count' => $run->snapshots->count(),
                    'created_at' => $run->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create competitor benchmark run
     */
    public function store(Request $request, Organization $organization, Audit $audit)
    {
        $this->authorize('manage', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'keywords' => ['required', 'array', 'min:1', 'max:10'],
            'keywords.*' => ['string', 'max:100'],
            'country' => ['nullable', 'string', 'max:10'],
        ]);

        $run = CompetitorRun::create([
            'organization_id' => $organization->id,
            'audit_id' => $audit->id,
            'keywords' => $validated['keywords'],
            'country' => $validated['country'] ?? $organization->country ?? 'us',
            'status' => CompetitorRun::STATUS_QUEUED,
        ]);

        // Dispatch job
        RunCompetitorBenchmarkJob::dispatch($run->id);

        return redirect()->route('competitors.index', [
            'organization' => $organization->id,
            'audit' => $audit->id,
        ])->with('success', 'Competitor benchmark started.');
    }

    /**
     * Show competitor run details
     */
    public function show(Organization $organization, Audit $audit, CompetitorRun $competitorRun)
    {
        $this->authorize('view', $organization);

        if ($competitorRun->audit_id !== $audit->id || $competitorRun->organization_id !== $organization->id) {
            abort(403);
        }

        $competitorRun->load('snapshots');
        
        // Get AI summary if available
        $summary = \App\Models\AiGeneration::where('audit_id', $audit->id)
            ->where('type', \App\Models\AiGeneration::TYPE_COMPETITOR_SUMMARY)
            ->where('status', \App\Models\AiGeneration::STATUS_COMPLETED)
            ->latest()
            ->first();

        return Inertia::render('Audits/Competitors/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'audit' => [
                'id' => $audit->id,
                'url' => $audit->url,
            ],
            'run' => [
                'id' => $competitorRun->id,
                'keywords' => $competitorRun->keywords,
                'country' => $competitorRun->country,
                'status' => $competitorRun->status,
                'snapshots' => $competitorRun->snapshots->map(function ($snapshot) {
                    return [
                        'id' => $snapshot->id,
                        'keyword' => $snapshot->keyword,
                        'competitor_url' => $snapshot->competitor_url,
                        'domain' => $snapshot->domain,
                        'title' => $snapshot->title,
                        'meta_description' => $snapshot->meta_description,
                        'word_count' => $snapshot->word_count,
                        'page_weight_bytes' => $snapshot->page_weight_bytes,
                    ];
                }),
            ],
            'summary' => $summary?->output,
        ]);
    }
}

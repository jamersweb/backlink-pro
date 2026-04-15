<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainBacklinkRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BacklinksCheckerController extends Controller
{
    /**
     * Backlinks checker hub (domain selector + latest status).
     */
    public function index()
    {
        $userId = Auth::id();

        $latestRunsSubquery = DomainBacklinkRun::query()
            ->select('domain_id', DB::raw('MAX(id) as latest_run_id'))
            ->where('user_id', $userId)
            ->groupBy('domain_id');

        $domains = Domain::query()
            ->where('domains.user_id', $userId)
            ->leftJoinSub($latestRunsSubquery, 'latest_runs', function ($join) {
                $join->on('latest_runs.domain_id', '=', 'domains.id');
            })
            ->leftJoin('domain_backlink_runs as latest_run', 'latest_run.id', '=', 'latest_runs.latest_run_id')
            ->orderBy('domains.name')
            ->get([
                'domains.id',
                'domains.name',
                'domains.host',
                'domains.url',
                'domains.status',
                'latest_run.id as latest_run_id',
                'latest_run.status as latest_run_status',
                'latest_run.summary_json as latest_run_summary',
                'latest_run.finished_at as latest_run_finished_at',
            ])
            ->map(function ($row) {
                $summary = $row->latest_run_summary;
                if (is_string($summary)) {
                    $decoded = json_decode($summary, true);
                    $summary = is_array($decoded) ? $decoded : null;
                }

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'host' => $row->host,
                    'url' => $row->url,
                    'status' => $row->status,
                    'latest_run_id' => $row->latest_run_id,
                    'latest_run_status' => $row->latest_run_status,
                    'latest_run_finished_at' => $row->latest_run_finished_at,
                    'latest_summary' => $summary,
                ];
            })
            ->values();

        $stats = [
            'total_domains' => $domains->count(),
            'completed_runs' => $domains->where('latest_run_status', DomainBacklinkRun::STATUS_COMPLETED)->count(),
            'in_progress_runs' => $domains->whereIn('latest_run_status', [
                DomainBacklinkRun::STATUS_QUEUED,
                DomainBacklinkRun::STATUS_RUNNING,
            ])->count(),
        ];

        return Inertia::render('BacklinksChecker/Index', [
            'domains' => $domains,
            'stats' => $stats,
        ]);
    }
}


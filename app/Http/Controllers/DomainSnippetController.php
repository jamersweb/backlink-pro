<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\SnippetInstallation;
use App\Models\SnippetEvent;
use App\Models\SnippetPerformance;
use App\Models\SnippetCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DomainSnippetController extends Controller
{
    /**
     * Show snippet management page
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $installation = $domain->snippetInstallation;
        
        // Get top pages (last 7 days)
        $topPages = SnippetEvent::where('domain_id', $domain->id)
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->select('path', DB::raw('SUM(views) as total_views'), DB::raw('SUM(uniques) as total_uniques'))
            ->groupBy('path')
            ->orderByDesc('total_views')
            ->limit(20)
            ->get();

        // Get views chart data (last 14 days)
        $chartData = SnippetEvent::where('domain_id', $domain->id)
            ->where('date', '>=', Carbon::now()->subDays(14))
            ->select('date', DB::raw('SUM(views) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get performance data
        $performance = SnippetPerformance::where('domain_id', $domain->id)
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->select('path', DB::raw('AVG(avg_load_ms) as avg_load'), DB::raw('AVG(avg_ttfb_ms) as avg_ttfb'), DB::raw('SUM(samples) as samples'))
            ->groupBy('path')
            ->orderByDesc('samples')
            ->limit(20)
            ->get();

        return Inertia::render('Domains/Snippet/Index', [
            'domain' => $domain,
            'installation' => $installation,
            'topPages' => $topPages,
            'chartData' => $chartData,
            'performance' => $performance,
        ]);
    }

    /**
     * Update snippet settings
     */
    public function updateSettings(Request $request, Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'tracking' => 'boolean',
            'performance' => 'boolean',
        ]);

        $installation = SnippetInstallation::firstOrCreate(
            ['domain_id' => $domain->id],
            [
                'key' => $domain->meta_snippet_key,
                'status' => SnippetInstallation::STATUS_UNKNOWN,
                'settings_json' => ['tracking' => true, 'performance' => false],
            ]
        );

        $settings = $installation->settings_json ?? [];
        $settings['tracking'] = $validated['tracking'] ?? $settings['tracking'] ?? true;
        $settings['performance'] = $validated['performance'] ?? $settings['performance'] ?? false;

        $installation->update(['settings_json' => $settings]);

        return back()->with('success', 'Settings updated');
    }

    /**
     * Send verify command
     */
    public function sendVerifyCommand(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        SnippetCommand::create([
            'domain_id' => $domain->id,
            'command' => SnippetCommand::COMMAND_VERIFY,
            'status' => SnippetCommand::STATUS_QUEUED,
        ]);

        return back()->with('success', 'Verify command sent. The snippet will ping within 60 seconds.');
    }

    /**
     * Send refresh meta command
     */
    public function sendRefreshCommand(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'paths' => 'nullable|array',
            'paths.*' => 'string',
        ]);

        SnippetCommand::create([
            'domain_id' => $domain->id,
            'command' => SnippetCommand::COMMAND_REFRESH_META,
            'status' => SnippetCommand::STATUS_QUEUED,
            'payload_json' => ['paths' => $validated['paths'] ?? []],
        ]);

        return back()->with('success', 'Refresh command sent.');
    }
}

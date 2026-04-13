<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $googleSeoAccount = $user->connectedAccounts()
            ->google()
            ->service(ConnectedAccount::SERVICE_SEO)
            ->active()
            ->latest()
            ->first();

        $projects = Schema::hasTable('projects')
            ? $user->projects()
                ->latest()
                ->get()
                ->map(fn (Project $project) => $this->transformProject($project))
            : collect();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'googleStatus' => [
                'seo_connected' => (bool) $googleSeoAccount,
                'ga4_connected' => (bool) $user->google_connected_at,
                'google_email' => $user->google_email ?: $googleSeoAccount?->email,
            ],
            'storageReady' => Schema::hasTable('projects'),
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('projects')) {
            return back()->with('error', 'Projects storage is not ready yet. Please run the latest migration first.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_url' => ['required', 'url', 'max:2048'],
        ]);

        $project = $request->user()->projects()->create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        if (!Schema::hasTable('projects')) {
            return redirect()->route('projects.index')
                ->with('error', 'Projects storage is not ready yet. Please run the latest migration first.');
        }

        abort_unless($project->user_id === Auth::id(), 403);

        $user = Auth::user();
        $googleSeoAccount = $user->connectedAccounts()
            ->google()
            ->service(ConnectedAccount::SERVICE_SEO)
            ->active()
            ->latest()
            ->first();

        return Inertia::render('Projects/Show', [
            'project' => $this->transformProject($project),
            'googleStatus' => [
                'seo_connected' => (bool) $googleSeoAccount,
                'ga4_connected' => (bool) $user->google_connected_at,
                'google_email' => $user->google_email ?: $googleSeoAccount?->email,
            ],
        ]);
    }

    public function update(Request $request, Project $project)
    {
        if (!Schema::hasTable('projects')) {
            return back()->with('error', 'Projects storage is not ready yet. Please run the latest migration first.');
        }

        abort_unless($project->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_url' => ['required', 'url', 'max:2048'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        if (!Schema::hasTable('projects')) {
            return redirect()->route('projects.index')
                ->with('error', 'Projects storage is not ready yet. Please run the latest migration first.');
        }

        abort_unless($project->user_id === Auth::id(), 403);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    protected function transformProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'project_url' => $project->project_url,
            'host' => parse_url($project->project_url, PHP_URL_HOST),
            'ga4_connected_at' => $project->ga4_connected_at?->toIso8601String(),
            'gsc_connected_at' => $project->gsc_connected_at?->toIso8601String(),
            'created_at' => $project->created_at?->toIso8601String(),
            'updated_at' => $project->updated_at?->toIso8601String(),
        ];
    }
}

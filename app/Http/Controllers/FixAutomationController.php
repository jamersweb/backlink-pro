<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditFixCandidate;
use App\Models\AuditPatch;
use App\Models\Organization;
use App\Models\Repo;
use App\Models\RepoConnection;
use App\Jobs\GenerateFixCandidatesJob;
use App\Jobs\GeneratePatchJob;
use App\Jobs\OpenPullRequestJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class FixAutomationController extends Controller
{
    /**
     * Show fix automation tab for audit
     */
    public function index(Organization $organization, Audit $audit)
    {
        $this->authorize('view', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $candidates = $audit->fixCandidates()->with('patches')->get();
        $repos = $organization->repos()->where('is_active', true)->get();

        return Inertia::render('Audits/FixAutomation', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'audit' => [
                'id' => $audit->id,
                'url' => $audit->url,
            ],
            'candidates' => $candidates->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'code' => $candidate->code,
                    'title' => $candidate->title,
                    'target_platform' => $candidate->target_platform,
                    'risk' => $candidate->risk,
                    'confidence' => $candidate->confidence,
                    'status' => $candidate->status,
                    'patches' => $candidate->patches->map(function ($patch) {
                        return [
                            'id' => $patch->id,
                            'pr_url' => $patch->pr_url,
                            'status' => $patch->status,
                            'files_touched' => $patch->files_touched,
                        ];
                    }),
                ];
            }),
            'repos' => $repos->map(function ($repo) {
                return [
                    'id' => $repo->id,
                    'repo_full_name' => $repo->repo_full_name,
                    'language_hint' => $repo->language_hint,
                ];
            }),
        ]);
    }

    /**
     * Generate fix candidates
     */
    public function generateCandidates(Request $request, Organization $organization, Audit $audit)
    {
        $this->authorize('manage', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'repo_id' => ['nullable', 'exists:repos,id'],
        ]);

        GenerateFixCandidatesJob::dispatch($audit->id, $validated['repo_id'] ?? null);

        return redirect()->back()->with('success', 'Fix candidates generation started.');
    }

    /**
     * Generate patch for candidate
     */
    public function generatePatch(Organization $organization, Audit $audit, AuditFixCandidate $candidate)
    {
        $this->authorize('manage', $organization);

        if ($candidate->audit_id !== $audit->id) {
            abort(403);
        }

        GeneratePatchJob::dispatch($candidate->id);

        return redirect()->back()->with('success', 'Patch generation started.');
    }

    /**
     * Download patch bundle
     */
    public function downloadPatch(Organization $organization, Audit $audit, AuditPatch $patch)
    {
        $this->authorize('view', $organization);

        if ($patch->fixCandidate->audit_id !== $audit->id) {
            abort(403);
        }

        // Create ZIP with patch files
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/temp/patch-' . $patch->id . '.zip');
        @mkdir(dirname($zipPath), 0755, true);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString('patch.diff', $patch->patch_unified_diff);
            if ($patch->apply_instructions) {
                $zip->addFromString('APPLY.md', $patch->apply_instructions);
            }
            if ($patch->test_instructions) {
                $zip->addFromString('TEST.md', $patch->test_instructions);
            }
            $zip->close();
        }

        return response()->download($zipPath, 'patch-' . $patch->id . '.zip')->deleteFileAfterSend();
    }

    /**
     * Open pull request
     */
    public function openPR(Organization $organization, Audit $audit, AuditPatch $patch)
    {
        $this->authorize('manage', $organization);

        if ($patch->fixCandidate->audit_id !== $audit->id) {
            abort(403);
        }

        if (!$patch->repo) {
            return redirect()->back()->with('error', 'No repository connected for this patch.');
        }

        OpenPullRequestJob::dispatch($patch->id);

        return redirect()->back()->with('success', 'Pull request creation started.');
    }

    /**
     * Connect GitHub repository
     */
    public function connectGithub(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'access_token' => ['required', 'string'],
            'account_name' => ['required', 'string'],
        ]);

        // Verify token by fetching user
        $response = Http::withHeaders([
            'Authorization' => "token {$validated['access_token']}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get('https://api.github.com/user');

        if (!$response->successful()) {
            return redirect()->back()->with('error', 'Invalid GitHub token.');
        }

        $connection = RepoConnection::create([
            'organization_id' => $organization->id,
            'provider' => RepoConnection::PROVIDER_GITHUB,
            'account_name' => $validated['account_name'],
            'access_token' => $validated['access_token'], // Will be encrypted via accessor
        ]);

        return redirect()->back()->with('success', 'GitHub connection established.');
    }

    /**
     * List repositories
     */
    public function listRepos(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $connection = $organization->repoConnections()->where('provider', 'github')->first();
        if (!$connection) {
            return response()->json(['repos' => []]);
        }

        $response = Http::withHeaders([
            'Authorization' => "token {$connection->access_token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get('https://api.github.com/user/repos', [
            'per_page' => 100,
            'type' => 'all',
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to fetch repositories'], 500);
        }

        return response()->json(['repos' => $response->json()]);
    }

    /**
     * Select repository
     */
    public function selectRepo(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'repo_full_name' => ['required', 'string'],
            'language_hint' => ['required', 'string', 'in:laravel,nextjs,wordpress,shopify,unknown'],
        ]);

        $connection = $organization->repoConnections()->where('provider', 'github')->first();
        if (!$connection) {
            return redirect()->back()->with('error', 'No GitHub connection found.');
        }

        // Get default branch
        $response = Http::withHeaders([
            'Authorization' => "token {$connection->access_token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/repos/{$validated['repo_full_name']}");

        if (!$response->successful()) {
            return redirect()->back()->with('error', 'Failed to fetch repository details.');
        }

        $repoData = $response->json();
        $defaultBranch = $repoData['default_branch'] ?? 'main';

        Repo::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'repo_full_name' => $validated['repo_full_name'],
            ],
            [
                'repo_connection_id' => $connection->id,
                'provider' => RepoConnection::PROVIDER_GITHUB,
                'default_branch' => $defaultBranch,
                'language_hint' => $validated['language_hint'],
                'is_active' => true,
            ]
        );

        return redirect()->back()->with('success', 'Repository selected.');
    }
}

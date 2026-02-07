<?php

namespace App\Services\FixAutomation;

use App\Models\AuditPatch;
use App\Models\Repo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubPullRequestService
{
    /**
     * Create a pull request for a patch
     */
    public function createPullRequest(AuditPatch $patch): ?string
    {
        $repo = $patch->repo;
        if (!$repo) {
            return null;
        }

        $connection = $repo->connection;
        $token = $connection->access_token;

        try {
            // Create branch
            $branchName = $this->createBranch($repo, $token, $patch);
            
            // Apply file changes
            $this->applyFileChanges($repo, $token, $branchName, $patch);
            
            // Create PR
            $prUrl = $this->openPullRequest($repo, $token, $branchName, $patch);
            
            $patch->update([
                'branch_name' => $branchName,
                'pr_url' => $prUrl,
                'status' => 'pr_opened',
            ]);

            return $prUrl;

        } catch (\Exception $e) {
            Log::error('GitHub PR creation failed', [
                'patch_id' => $patch->id,
                'error' => $e->getMessage(),
            ]);

            $patch->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create branch from default branch
     */
    protected function createBranch(Repo $repo, string $token, AuditPatch $patch): string
    {
        $branchName = 'backlinkpro/fix-' . $patch->id . '-' . time();

        // Get default branch SHA
        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->get("https://api.github.com/repos/{$repo->repo_full_name}/git/ref/heads/{$repo->default_branch}");

        if (!$response->successful()) {
            throw new \Exception("Failed to get default branch: " . $response->body());
        }

        $defaultSha = $response->json()['object']['sha'];

        // Create new branch
        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->post("https://api.github.com/repos/{$repo->repo_full_name}/git/refs", [
            'ref' => "refs/heads/{$branchName}",
            'sha' => $defaultSha,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to create branch: " . $response->body());
        }

        return $branchName;
    }

    /**
     * Apply file changes via GitHub API
     */
    protected function applyFileChanges(Repo $repo, string $token, string $branchName, AuditPatch $patch): void
    {
        $files = $patch->files_touched ?? [];
        $diff = $patch->patch_unified_diff;

        // Parse diff and apply changes (simplified - would parse unified diff properly)
        foreach ($files as $filePath) {
            // Get current file content
            $response = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("https://api.github.com/repos/{$repo->repo_full_name}/contents/{$filePath}", [
                'ref' => $branchName,
            ]);

            $currentContent = '';
            $currentSha = null;

            if ($response->successful()) {
                $fileData = $response->json();
                $currentContent = base64_decode($fileData['content']);
                $currentSha = $fileData['sha'];
            }

            // Apply patch (simplified - would properly apply unified diff)
            $newContent = $this->applyPatchToContent($currentContent, $diff, $filePath);

            // Update file
            Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json',
            ])->put("https://api.github.com/repos/{$repo->repo_full_name}/contents/{$filePath}", [
                'message' => "chore(seo): {$patch->fixCandidate->title}",
                'content' => base64_encode($newContent),
                'branch' => $branchName,
                'sha' => $currentSha,
            ]);
        }
    }

    /**
     * Open pull request
     */
    protected function openPullRequest(Repo $repo, string $token, string $branchName, AuditPatch $patch): string
    {
        $title = "SEO Fix: {$patch->fixCandidate->title}";
        $body = $this->buildPRBody($patch);

        $response = Http::withHeaders([
            'Authorization' => "token {$token}",
            'Accept' => 'application/vnd.github.v3+json',
        ])->post("https://api.github.com/repos/{$repo->repo_full_name}/pulls", [
            'title' => $title,
            'body' => $body,
            'head' => $branchName,
            'base' => $repo->default_branch,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to create PR: " . $response->body());
        }

        return $response->json()['html_url'];
    }

    /**
     * Build PR body
     */
    protected function buildPRBody(AuditPatch $patch): string
    {
        $body = "## SEO Fix: {$patch->fixCandidate->title}\n\n";
        $body .= "**Risk Level:** {$patch->fixCandidate->risk}\n";
        $body .= "**Confidence:** {$patch->fixCandidate->confidence}%\n\n";
        
        if ($patch->apply_instructions) {
            $body .= "### Apply Instructions\n\n{$patch->apply_instructions}\n\n";
        }

        if ($patch->test_instructions) {
            $body .= "### Test Instructions\n\n{$patch->test_instructions}\n\n";
        }

        $body .= "---\n*Generated by BacklinkPro SEO Audit*\n";

        return $body;
    }

    /**
     * Apply patch to content (simplified)
     */
    protected function applyPatchToContent(string $currentContent, string $diff, string $filePath): string
    {
        // Simplified - would properly parse unified diff
        // For MVP, just append the new content
        $lines = explode("\n", $diff);
        $newLines = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '+') === 0) {
                $newLines[] = substr($line, 1);
            }
        }

        $newContent = implode("\n", $newLines);
        
        // Insert before </head> if exists
        if (strpos($currentContent, '</head>') !== false) {
            return str_replace('</head>', $newContent . "\n</head>", $currentContent);
        }

        return $currentContent . "\n" . $newContent;
    }

}

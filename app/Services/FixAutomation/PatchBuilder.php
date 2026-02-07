<?php

namespace App\Services\FixAutomation;

use App\Models\Audit;
use App\Models\AuditFixCandidate;
use App\Models\AuditIssue;
use App\Models\FixTemplate;
use App\Models\Repo;

class PatchBuilder
{
    /**
     * Generate fix candidates from audit issues
     */
    public function generateCandidates(Audit $audit, ?Repo $repo = null): array
    {
        $candidates = [];
        $platform = $repo ? $repo->language_hint : 'generic';

        // Map issue codes to fix codes
        $issueToFixMap = [
            'SOCIAL_TWITTER_CARDS_MISSING' => 'FIX_ADD_TWITTER_CARDS',
            'SOCIAL_OG_TAGS_MISSING' => 'FIX_ADD_OG_TAGS',
            'TECHNICAL_CANONICAL_MISSING' => 'FIX_ADD_CANONICAL',
            'ONPAGE_TITLE_MISSING' => 'FIX_ADD_META_TITLE',
            'ONPAGE_META_DESCRIPTION_MISSING' => 'FIX_ADD_META_DESCRIPTION',
        ];

        $issues = $audit->issues()->get();
        
        foreach ($issues as $issue) {
            $fixCode = $issueToFixMap[$issue->code] ?? null;
            if (!$fixCode) {
                continue; // Skip unsupported issues
            }

            $template = FixTemplate::where('key', $this->getTemplateKey($fixCode, $platform))
                ->first();

            if (!$template) {
                continue; // No template for this platform
            }

            $candidate = AuditFixCandidate::create([
                'audit_id' => $audit->id,
                'issue_id' => $issue->id,
                'code' => $fixCode,
                'title' => "Fix: {$issue->title}",
                'target_platform' => $platform,
                'risk' => $this->assessRisk($fixCode),
                'confidence' => $this->calculateConfidence($template, $repo),
                'status' => 'draft',
            ]);

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    /**
     * Generate patch for a candidate
     */
    public function generatePatch(AuditFixCandidate $candidate, ?Repo $repo = null): ?\App\Models\AuditPatch
    {
        $template = FixTemplate::where('key', $this->getTemplateKey($candidate->code, $candidate->target_platform))
            ->first();

        if (!$template) {
            return null;
        }

        $strategy = $template->patch_strategy;
        $targetFiles = $this->findTargetFiles($repo, $strategy);

        if (empty($targetFiles)) {
            // Create manual steps candidate instead
            $candidate->update([
                'status' => AuditFixCandidate::STATUS_GENERATED,
                'generated_summary' => 'Manual steps required - target files not found automatically.',
            ]);
            return null;
        }

        // Build unified diff
        $patches = [];
        $filesTouched = [];
        foreach ($targetFiles as $file) {
            $patch = $this->buildFilePatch($file, $strategy, $candidate);
            if ($patch) {
                $patches[] = $patch;
                $filesTouched[] = $file;
            }
        }

        $unifiedDiff = implode("\n---\n", $patches);

        // Create patch record
        $auditPatch = \App\Models\AuditPatch::create([
            'audit_fix_candidate_id' => $candidate->id,
            'repo_id' => $repo?->id,
            'patch_unified_diff' => $unifiedDiff,
            'files_touched' => $filesTouched,
            'apply_instructions' => $this->buildApplyInstructions($candidate, $targetFiles),
            'test_instructions' => $this->buildTestInstructions($candidate),
            'status' => \App\Models\AuditPatch::STATUS_READY,
        ]);

        $candidate->update([
            'status' => AuditFixCandidate::STATUS_GENERATED,
        ]);

        return $auditPatch;
    }

    /**
     * Find target files based on strategy
     */
    protected function findTargetFiles(?Repo $repo, array $strategy): array
    {
        $filePatterns = $strategy['file_patterns'] ?? [];
        $foundFiles = [];

        // For MVP, return common patterns (in production, use GitHub API to list files)
        foreach ($filePatterns as $pattern) {
            $foundFiles[] = $pattern; // Simplified - would use GitHub API in production
        }

        return $foundFiles;
    }

    /**
     * Build patch for a single file
     */
    protected function buildFilePatch(string $filePath, array $strategy, AuditFixCandidate $candidate): ?string
    {
        $insertion = $strategy['insertion'] ?? null;
        $marker = $strategy['marker'] ?? null;

        if (!$insertion) {
            return null;
        }

        // Build unified diff format
        $diff = "--- a/{$filePath}\n";
        $diff .= "+++ b/{$filePath}\n";
        $diff .= "@@ -1,0 +1,{$this->countLines($insertion)} @@\n";
        
        if ($marker) {
            $diff .= "+<!-- BacklinkPro:meta:start -->\n";
        }
        
        $diff .= "+{$insertion}\n";
        
        if ($marker) {
            $diff .= "+<!-- BacklinkPro:meta:end -->\n";
        }

        return $diff;
    }

    /**
     * Get template key for fix code and platform
     */
    protected function getTemplateKey(string $fixCode, string $platform): string
    {
        $mapping = [
            'FIX_ADD_TWITTER_CARDS' => "{$platform}_twitter_cards_v1",
            'FIX_ADD_OG_TAGS' => "{$platform}_og_tags_v1",
            'FIX_ADD_CANONICAL' => "{$platform}_canonical_v1",
            'FIX_ADD_META_TITLE' => "{$platform}_meta_title_v1",
            'FIX_ADD_META_DESCRIPTION' => "{$platform}_meta_description_v1",
        ];

        return $mapping[$fixCode] ?? "{$platform}_generic_v1";
    }

    /**
     * Assess risk level
     */
    protected function assessRisk(string $fixCode): string
    {
        // Meta tags are low risk
        $lowRisk = ['FIX_ADD_TWITTER_CARDS', 'FIX_ADD_OG_TAGS', 'FIX_ADD_CANONICAL', 'FIX_ADD_META_TITLE', 'FIX_ADD_META_DESCRIPTION'];
        
        return in_array($fixCode, $lowRisk) ? 'low' : 'medium';
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence($template, ?Repo $repo): int
    {
        $confidence = 50; // Base confidence

        if ($repo && $repo->language_hint !== 'unknown') {
            $confidence += 20;
        }

        if ($template && isset($template->patch_strategy['file_patterns'])) {
            $confidence += 20;
        }

        return min(100, $confidence);
    }

    /**
     * Count lines in text
     */
    protected function countLines(string $text): int
    {
        return substr_count($text, "\n") + 1;
    }

    /**
     * Build apply instructions
     */
    protected function buildApplyInstructions(AuditFixCandidate $candidate, array $files): string
    {
        $instructions = "## Apply Instructions\n\n";
        $instructions .= "1. Review the patch diff above\n";
        $instructions .= "2. Apply changes to the following files:\n";
        
        foreach ($files as $file) {
            $instructions .= "   - {$file}\n";
        }
        
        $instructions .= "\n3. Test the changes locally\n";
        $instructions .= "4. Commit and push if satisfied\n";
        
        return $instructions;
    }

    /**
     * Build test instructions
     */
    protected function buildTestInstructions(AuditFixCandidate $candidate): string
    {
        $instructions = "## Test Instructions\n\n";
        $instructions .= "1. Verify meta tags appear in page source\n";
        $instructions .= "2. Check that no existing functionality is broken\n";
        $instructions .= "3. Validate HTML structure\n";
        $instructions .= "4. Test on mobile devices if applicable\n";
        
        return $instructions;
    }
}

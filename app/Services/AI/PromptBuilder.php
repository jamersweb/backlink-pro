<?php

namespace App\Services\AI;

use App\Models\Audit;
use App\Models\AiPromptTemplate;

class PromptBuilder
{
    protected LLMClientInterface $client;

    public function __construct(LLMClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Build prompt for report summary
     */
    public function buildReportSummaryPrompt(Audit $audit): array
    {
        $template = AiPromptTemplate::where('key', 'report_summary_v1')
            ->where('is_active', true)
            ->first();

        $systemPrompt = $template?->system_prompt ?? $this->getDefaultSystemPrompt();
        $userPromptTemplate = $template?->user_prompt ?? $this->getDefaultReportSummaryPrompt();

        // Build sanitized audit data
        $auditData = $this->sanitizeAuditData($audit);

        // Replace placeholders
        $userPrompt = $this->replacePlaceholders($userPromptTemplate, [
            'audit_data' => json_encode($auditData, JSON_PRETTY_PRINT),
        ]);

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'json_mode' => true,
        ];
    }

    /**
     * Build prompt for fix plan
     */
    public function buildFixPlanPrompt(Audit $audit): array
    {
        $template = AiPromptTemplate::where('key', 'fix_plan_v1')
            ->where('is_active', true)
            ->first();

        $systemPrompt = $template?->system_prompt ?? $this->getDefaultSystemPrompt();
        $userPromptTemplate = $template?->user_prompt ?? $this->getDefaultFixPlanPrompt();

        $auditData = $this->sanitizeAuditData($audit);
        $prioritizedIssues = $this->getPrioritizedIssues($audit);

        $userPrompt = $this->replacePlaceholders($userPromptTemplate, [
            'audit_data' => json_encode($auditData, JSON_PRETTY_PRINT),
            'prioritized_issues' => json_encode($prioritizedIssues, JSON_PRETTY_PRINT),
        ]);

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'json_mode' => true,
        ];
    }

    /**
     * Build prompt for snippet pack
     */
    public function buildSnippetPackPrompt(Audit $audit, array $issueCodes, string $platform): array
    {
        $template = AiPromptTemplate::where('key', 'snippet_pack_v1')
            ->where('is_active', true)
            ->first();

        $systemPrompt = $template?->system_prompt ?? $this->getDefaultSystemPrompt();
        $userPromptTemplate = $template?->user_prompt ?? $this->getDefaultSnippetPrompt();

        $issues = $audit->issues()
            ->whereIn('code', $issueCodes)
            ->get()
            ->map(fn($issue) => [
                'code' => $issue->code,
                'title' => $issue->title,
                'description' => $issue->description,
                'recommendation' => $issue->recommendation,
            ])
            ->toArray();

        $userPrompt = $this->replacePlaceholders($userPromptTemplate, [
            'issues' => json_encode($issues, JSON_PRETTY_PRINT),
            'platform' => $platform,
        ]);

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'json_mode' => true,
        ];
    }

    /**
     * Build prompt for chat answer
     */
    public function buildChatPrompt(Audit $audit, string $question, array $contextChunks = []): array
    {
        $template = AiPromptTemplate::where('key', 'chat_v1')
            ->where('is_active', true)
            ->first();

        $systemPrompt = $template?->system_prompt ?? $this->getDefaultChatSystemPrompt();
        
        $context = '';
        if (!empty($contextChunks)) {
            $context = "Context from audit:\n" . implode("\n\n", array_map(fn($c) => $c['content'], $contextChunks));
        }

        $userPrompt = "Question: {$question}\n\n{$context}\n\nAnswer based only on the provided context. If the answer is not in the context, say 'Not enough data in this audit to answer. Run deeper crawl or add keywords.'";

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'json_mode' => false, // Chat can be natural language
        ];
    }

    /**
     * Sanitize audit data (remove sensitive info)
     */
    protected function sanitizeAuditData(Audit $audit): array
    {
        $pages = $audit->pages()->limit(10)->get()->map(fn($page) => [
            'url' => $page->url,
            'status_code' => $page->status_code,
            'title' => $page->title,
            'title_len' => $page->title_len,
            'meta_description' => $page->meta_description,
            'meta_len' => $page->meta_len,
            'h1_count' => $page->h1_count,
            'word_count' => $page->word_count,
            'images_missing_alt' => $page->images_missing_alt,
        ]);

        $issues = $audit->issues()->get()->map(fn($issue) => [
            'code' => $issue->code,
            'title' => $issue->title,
            'description' => $issue->description,
            'impact' => $issue->impact,
            'effort' => $issue->effort,
            'affected_count' => $issue->affected_count,
        ]);

        $performance = $audit->performance_summary ?? [];
        $crawlStats = $audit->crawl_stats ?? [];

        return [
            'overall_score' => $audit->overall_score,
            'overall_grade' => $audit->overall_grade,
            'category_scores' => $audit->category_scores,
            'pages_scanned' => $audit->pages_scanned,
            'scan_date' => $audit->finished_at?->toDateString(),
            'pages' => $pages,
            'issues' => $issues,
            'performance' => $performance,
            'crawl_stats' => $crawlStats,
        ];
    }

    /**
     * Get prioritized issues (deterministic + AI)
     */
    protected function getPrioritizedIssues(Audit $audit): array
    {
        $issues = $audit->issues()->get();
        
        return $issues->map(function ($issue) {
            // Calculate priority score
            $severityWeight = match($issue->impact) {
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 1,
            };

            $effortWeight = match($issue->effort) {
                'easy' => 3,
                'medium' => 2,
                'hard' => 1,
                default => 2,
            };

            $priorityScore = $severityWeight * $effortWeight * ($issue->affected_count ?? 1);

            return [
                'code' => $issue->code,
                'title' => $issue->title,
                'description' => $issue->description,
                'impact' => $issue->impact,
                'effort' => $issue->effort,
                'affected_count' => $issue->affected_count,
                'priority_score' => $priorityScore,
            ];
        })
        ->sortByDesc('priority_score')
        ->take(10)
        ->values()
        ->toArray();
    }

    /**
     * Replace placeholders in template
     */
    protected function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }
        return $template;
    }

    /**
     * Default prompts
     */
    protected function getDefaultSystemPrompt(): string
    {
        return "You are an expert SEO consultant. Return valid JSON only. Use only the provided audit data. If insufficient data, state it clearly. No external assumptions.";
    }

    protected function getDefaultReportSummaryPrompt(): string
    {
        return "Analyze this SEO audit and generate a structured report summary:\n\n{{{audit_data}}}\n\nReturn JSON with executive_summary, priority_fixes, week_plan, and data_used sections.";
    }

    protected function getDefaultFixPlanPrompt(): string
    {
        return "Create a prioritized fix plan based on these issues:\n\n{{{prioritized_issues}}}\n\nAudit context:\n{{{audit_data}}}\n\nReturn JSON with priority_fixes (array) and week_plan (object with week_1, week_2, week_3).";
    }

    protected function getDefaultSnippetPrompt(): string
    {
        return "Generate code snippets for {{{platform}}} to fix these SEO issues:\n\n{{{issues}}}\n\nReturn JSON with snippets array, each with issue_code, platform, title, files, code, and notes.";
    }

    protected function getDefaultChatSystemPrompt(): string
    {
        return "You are an SEO assistant. Answer questions based ONLY on the provided audit context. If the answer is not in the context, say 'Not enough data in this audit to answer. Run deeper crawl or add keywords.' Never make assumptions.";
    }
}

<?php

namespace App\Services\AI;

use App\Models\Audit;
use App\Models\AuditKnowledgeChunk;
use Illuminate\Support\Facades\Log;

class RAGRetriever
{
    protected LLMClientInterface $client;

    public function __construct(LLMClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve relevant chunks for query
     */
    public function retrieve(Audit $audit, string $query, int $limit = 8): array
    {
        // For now, use simple text matching (can be enhanced with embeddings)
        // In production, use vector similarity search
        
        $chunks = AuditKnowledgeChunk::where('audit_id', $audit->id)
            ->get();

        if ($chunks->isEmpty()) {
            // Build chunks if they don't exist
            $this->buildChunks($audit);
            $chunks = AuditKnowledgeChunk::where('audit_id', $audit->id)->get();
        }

        // Simple keyword matching (replace with vector search in production)
        $queryTerms = explode(' ', strtolower($query));
        
        $scoredChunks = $chunks->map(function ($chunk) use ($queryTerms) {
            $content = strtolower($chunk->content);
            $score = 0;
            
            foreach ($queryTerms as $term) {
                if (strlen($term) > 2 && strpos($content, $term) !== false) {
                    $score += substr_count($content, $term);
                }
            }
            
            return [
                'chunk' => $chunk,
                'score' => $score,
            ];
        })
        ->filter(fn($item) => $item['score'] > 0)
        ->sortByDesc('score')
        ->take($limit)
        ->map(fn($item) => [
            'content' => $item['chunk']->content,
            'chunk_type' => $item['chunk']->chunk_type,
            'source_id' => $item['chunk']->source_id,
        ])
        ->values()
        ->toArray();

        return $scoredChunks;
    }

    /**
     * Build knowledge chunks from audit
     */
    public function buildChunks(Audit $audit): void
    {
        // Build chunks for issues
        $audit->issues()->each(function ($issue) use ($audit) {
            AuditKnowledgeChunk::create([
                'audit_id' => $audit->id,
                'chunk_type' => 'issue',
                'source_id' => $issue->id,
                'content' => "Issue: {$issue->title}\n\n{$issue->description}\n\nImpact: {$issue->impact}, Effort: {$issue->effort}\n\nAffected: {$issue->affected_count} pages",
            ]);
        });

        // Build chunks for pages
        $audit->pages()->limit(10)->each(function ($page) use ($audit) {
            AuditKnowledgeChunk::create([
                'audit_id' => $audit->id,
                'chunk_type' => 'page',
                'source_id' => $page->id,
                'content' => "Page: {$page->url}\n\nTitle: {$page->title}\n\nMeta: {$page->meta_description}\n\nWord count: {$page->word_count}, Images missing alt: {$page->images_missing_alt}",
            ]);
        });

        // Build chunk for performance
        if ($audit->performance_summary) {
            $perf = $audit->performance_summary;
            AuditKnowledgeChunk::create([
                'audit_id' => $audit->id,
                'chunk_type' => 'performance',
                'source_id' => null,
                'content' => "Performance Summary:\n\nMobile Score: " . ($perf['mobile_avg_score'] ?? 'N/A') . "\nDesktop Score: " . ($perf['desktop_avg_score'] ?? 'N/A') . "\nWorst LCP: " . ($perf['worst_lcp'] ?? 'N/A') . "ms",
            ]);
        }
    }
}

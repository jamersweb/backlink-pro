<?php

namespace App\Jobs\IndexCrawl;

use App\Models\IndexCrawlRun;
use App\Services\IndexCrawl\IndexCrawlAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunIndexCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 1;

    public function __construct(public int $runId)
    {
    }

    public function handle(IndexCrawlAnalyzer $analyzer): void
    {
        $run = IndexCrawlRun::with('domain')->findOrFail($this->runId);

        $run->update([
            'status' => IndexCrawlRun::STATUS_RUNNING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $summary = $analyzer->execute($run, $run->domain, $run->settings_json ?? []);

            $run->update([
                'status' => IndexCrawlRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'score' => $summary['score'] ?? null,
                'total_urls_discovered' => $summary['total_urls_discovered'] ?? 0,
                'total_urls_crawled' => $summary['total_urls_crawled'] ?? 0,
                'total_issues' => array_sum($summary['issue_totals'] ?? []),
                'summary_json' => $summary,
            ]);
        } catch (\Throwable $e) {
            Log::error('Index crawl failed', [
                'run_id' => $this->runId,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => IndexCrawlRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }
    }
}

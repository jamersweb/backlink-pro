<?php

namespace App\Jobs\Audits;

use App\Models\DomainAuditMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPageSpeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 2;
    public $queue = 'audits';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public string $url,
        public string $strategy = 'mobile'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = env('PAGESPEED_API_KEY');

        if (!$apiKey) {
            Log::debug('PageSpeed API key not configured, skipping', [
                'audit_id' => $this->auditId,
                'url' => $this->url,
            ]);
            return;
        }

        try {
            $response = Http::timeout(60)
                ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                    'url' => $this->url,
                    'strategy' => $this->strategy,
                    'key' => $apiKey,
                    'category' => ['performance'],
                ]);

            if (!$response->successful()) {
                Log::warning('PageSpeed API request failed', [
                    'audit_id' => $this->auditId,
                    'url' => $this->url,
                    'status' => $response->status(),
                ]);
                return;
            }

            $data = $response->json();

            // Extract metrics
            $lighthouseResult = $data['lighthouseResult'] ?? [];
            $audits = $lighthouseResult['audits'] ?? [];
            $categories = $lighthouseResult['categories'] ?? [];
            $performance = $categories['performance'] ?? [];

            $performanceScore = isset($performance['score']) ? (int) ($performance['score'] * 100) : null;

            // Extract Core Web Vitals
            $lcp = $audits['largest-contentful-paint'] ?? [];
            $lcpMs = isset($lcp['numericValue']) ? (int) $lcp['numericValue'] : null;

            $cls = $audits['cumulative-layout-shift'] ?? [];
            $clsValue = isset($cls['numericValue']) ? $cls['numericValue'] : null;
            $clsX1000 = $clsValue ? (int) ($clsValue * 1000) : null;

            $inp = $audits['interaction-to-next-paint'] ?? [];
            $inpMs = isset($inp['numericValue']) ? (int) $inp['numericValue'] : null;

            $fcp = $audits['first-contentful-paint'] ?? [];
            $fcpMs = isset($fcp['numericValue']) ? (int) $fcp['numericValue'] : null;

            $ttfb = $audits['server-response-time'] ?? [];
            $ttfbMs = isset($ttfb['numericValue']) ? (int) $ttfb['numericValue'] : null;

            // Save metric
            DomainAuditMetric::updateOrCreate(
                [
                    'domain_audit_id' => $this->auditId,
                    'url' => $this->url,
                    'strategy' => $this->strategy,
                ],
                [
                    'performance_score' => $performanceScore,
                    'lcp_ms' => $lcpMs,
                    'cls_x1000' => $clsX1000,
                    'inp_ms' => $inpMs,
                    'fcp_ms' => $fcpMs,
                    'ttfb_ms' => $ttfbMs,
                    'raw_json' => $data,
                ]
            );
        } catch (\Exception $e) {
            Log::error('PageSpeed fetch failed', [
                'audit_id' => $this->auditId,
                'url' => $this->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

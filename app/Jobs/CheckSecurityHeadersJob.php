<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckSecurityHeadersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public int $pageId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        $page = AuditPage::find($this->pageId);
        
        if (!$audit || !$page) {
            Log::warning("Audit or page not found", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
            ]);
            return;
        }

        try {
            // Send HEAD request to check headers
            $response = Http::timeout(10)->head($page->url);
            
            $headers = [
                'hsts' => $this->checkHsts($response),
                'x_frame_options' => $this->checkXFrameOptions($response),
                'x_content_type_options' => $this->checkXContentTypeOptions($response),
                'referrer_policy' => $this->checkReferrerPolicy($response),
                'permissions_policy' => $this->checkPermissionsPolicy($response),
                'csp' => $this->checkCsp($response),
            ];

            // Store security headers
            $page->security_headers = $headers;
            $page->save();

        } catch (\Exception $e) {
            Log::error("CheckSecurityHeadersJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Check HSTS header
     */
    protected function checkHsts($response): bool
    {
        $header = $response->header('Strict-Transport-Security');
        return !empty($header);
    }

    /**
     * Check X-Frame-Options header
     */
    protected function checkXFrameOptions($response): bool
    {
        $header = $response->header('X-Frame-Options');
        return !empty($header);
    }

    /**
     * Check X-Content-Type-Options header
     */
    protected function checkXContentTypeOptions($response): bool
    {
        $header = $response->header('X-Content-Type-Options');
        return !empty($header) && stripos($header, 'nosniff') !== false;
    }

    /**
     * Check Referrer-Policy header
     */
    protected function checkReferrerPolicy($response): bool
    {
        $header = $response->header('Referrer-Policy');
        return !empty($header);
    }

    /**
     * Check Permissions-Policy header
     */
    protected function checkPermissionsPolicy($response): bool
    {
        $header = $response->header('Permissions-Policy');
        return !empty($header);
    }

    /**
     * Check Content-Security-Policy header
     */
    protected function checkCsp($response): bool
    {
        $header = $response->header('Content-Security-Policy');
        return !empty($header);
    }
}

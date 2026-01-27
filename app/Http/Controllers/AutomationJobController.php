<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\AutomationJob;
use App\Models\BacklinkAttempt;
use App\Models\BacklinkPageSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

/**
 * Controller for worker to update job status
 * This can be called by Python worker via API or direct DB updates
 */
class AutomationJobController extends Controller
{
    /**
     * Update job result (called by worker)
     */
    public function updateResult(Request $request, Domain $domain, AutomationJob $job)
    {
        // Validate worker token or use DB direct updates
        // For MVP, allow if job belongs to domain

        if ($job->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:success,failed',
            'result_json' => 'nullable|array',
            'error_code' => 'nullable|string',
            'error_message' => 'nullable|string',
            'signals_json' => 'nullable|array', // Page signals from worker
        ]);

        DB::transaction(function() use ($job, $validated) {
            $job->update([
                'status' => $validated['status'] === 'success' ? AutomationJob::STATUS_SUCCESS : AutomationJob::STATUS_FAILED,
                'finished_at' => now(),
                'result_json' => $validated['result_json'] ?? null,
                'error_code' => $validated['error_code'] ?? null,
                'error_message' => $validated['error_message'] ?? null,
            ]);

            // Create training example
            $attempt = BacklinkAttempt::create([
                'user_id' => $job->user_id,
                'domain_id' => $job->domain_id,
                'campaign_id' => $job->campaign_id,
                'job_id' => $job->id,
                'target_url' => $job->target->url,
                'target_domain' => parse_url($job->target->url, PHP_URL_HOST) ?? '',
                'detected_platform' => $this->detectPlatform($job->target->url),
                'action_attempted' => $job->action,
                'result' => $validated['status'] === 'success' ? BacklinkAttempt::RESULT_SUCCESS : BacklinkAttempt::RESULT_FAILED,
                'failure_reason' => $validated['error_code'] ?? null,
                'created_backlink_url' => $validated['result_json']['created_url'] ?? null,
                'metadata_json' => [
                    'anchor' => $job->target->anchor_text,
                    'target_link' => $job->target->target_link,
                ],
                'created_at' => now(),
            ]);

            // Store page signals if provided
            if (!empty($validated['signals_json'])) {
                BacklinkPageSignal::create([
                    'attempt_id' => $attempt->id,
                    'http_status' => $validated['signals_json']['http_status'] ?? null,
                    'content_type' => $validated['signals_json']['content_type'] ?? null,
                    'has_comment_form' => $validated['signals_json']['has_comment_form'] ?? false,
                    'has_login_form' => $validated['signals_json']['has_login_form'] ?? false,
                    'has_register_link' => $validated['signals_json']['has_register_link'] ?? false,
                    'has_captcha' => $validated['signals_json']['has_captcha'] ?? false,
                    'is_cloudflare' => $validated['signals_json']['is_cloudflare'] ?? false,
                    'has_profile_fields' => $validated['signals_json']['has_profile_fields'] ?? false,
                    'has_forum_thread_ui' => $validated['signals_json']['has_forum_thread_ui'] ?? false,
                    'has_editor_wysiwyg' => $validated['signals_json']['has_editor_wysiwyg'] ?? false,
                    'has_email_verify_hint' => $validated['signals_json']['has_email_verify_hint'] ?? false,
                    'outbound_links_count' => $validated['signals_json']['outbound_links_count'] ?? null,
                    'text_length' => $validated['signals_json']['text_length'] ?? null,
                    'signals_json' => $validated['signals_json'],
                    'created_at' => now(),
                ]);
            }

            // Update campaign totals
            $job->campaign->updateTotals();
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * Detect platform from URL
     */
    protected function detectPlatform(string $url): ?string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        if (str_contains($host, 'wordpress.com') || str_contains($path, '/wp-')) {
            return 'wordpress';
        }
        if (str_contains($host, 'blogspot') || str_contains($host, 'blogger')) {
            return 'blogger';
        }
        if (str_contains($host, 'medium.com')) {
            return 'medium';
        }
        if (str_contains($host, 'reddit.com')) {
            return 'reddit';
        }
        if (preg_match('#\.(phpbb|vbulletin|mybb|smf|xenforo)\.#i', $host)) {
            return 'forum';
        }

        return null;
    }
}



<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SiteAccount;
use App\Models\AutomationTask;
use App\Services\GmailService;
use Illuminate\Support\Facades\Log;

class WaitForVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 60; // Try 60 times (30 minutes if run every 30 seconds)
    public $timeout = 300; // 5 minutes timeout per attempt
    public $backoff = 30; // Wait 30 seconds between attempts

    protected $siteAccount;
    protected $campaignId;

    /**
     * Create a new job instance.
     */
    public function __construct(SiteAccount $siteAccount, int $campaignId)
    {
        $this->siteAccount = $siteAccount;
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the connected account for this campaign
            $campaign = \App\Models\Campaign::findOrFail($this->campaignId);
            $connectedAccount = $campaign->gmailAccount;

            if (!$connectedAccount) {
                Log::warning('No connected account found for campaign', [
                    'campaign_id' => $this->campaignId,
                    'site_account_id' => $this->siteAccount->id,
                ]);
                $this->fail('No connected Gmail account found');
                return;
            }

            // Initialize Gmail service
            $gmailService = new GmailService($connectedAccount);

            // Search for verification emails
            $query = sprintf(
                'to:%s subject:"verify" OR subject:"confirm" OR subject:"activate" OR subject:"activation" after:%s',
                $connectedAccount->email,
                now()->subHours(24)->format('Y/m/d')
            );

            $emails = $gmailService->searchEmails($query, 10);

            foreach ($emails as $email) {
                // Extract verification links
                $links = $gmailService->extractVerificationLinks($email['body']);

                if (!empty($links)) {
                    // Update site account
                    $this->siteAccount->update([
                        'verification_link' => $links[0],
                        'email_verification_status' => SiteAccount::EMAIL_STATUS_FOUND,
                        'last_verification_check_at' => now(),
                        'status' => SiteAccount::STATUS_VERIFIED,
                    ]);

                    // Create automation task to click verification link
                    AutomationTask::create([
                        'campaign_id' => $this->campaignId,
                        'site_account_id' => $this->siteAccount->id,
                        'type' => AutomationTask::TYPE_EMAIL_CONFIRMATION_CLICK,
                        'status' => AutomationTask::STATUS_PENDING,
                        'payload' => [
                            'verification_link' => $links[0], // Use first link
                            'site_account_id' => $this->siteAccount->id,
                        ],
                    ]);

                    Log::info('Verification email found and task created', [
                        'site_account_id' => $this->siteAccount->id,
                        'campaign_id' => $this->campaignId,
                        'email_id' => $email['id'],
                    ]);

                    return; // Success, stop retrying
                }
            }

            // No verification email found yet, release job to retry
            if ($this->attempts() >= $this->tries) {
                Log::warning('Verification email not found after max attempts', [
                    'site_account_id' => $this->siteAccount->id,
                    'campaign_id' => $this->campaignId,
                    'attempts' => $this->attempts(),
                ]);
                $this->siteAccount->update([
                    'email_verification_status' => SiteAccount::EMAIL_STATUS_TIMEOUT,
                    'status' => SiteAccount::STATUS_FAILED,
                    'last_verification_check_at' => now(),
                ]);
                $this->fail('Verification email not found after maximum attempts');
            } else {
                // Release job to retry later
                $this->release($this->backoff);
            }
        } catch (\Exception $e) {
            Log::error('WaitForVerificationEmailJob failed', [
                'error' => $e->getMessage(),
                'site_account_id' => $this->siteAccount->id,
                'campaign_id' => $this->campaignId,
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->fail($e->getMessage());
            } else {
                $this->release($this->backoff);
            }
        }
    }
}


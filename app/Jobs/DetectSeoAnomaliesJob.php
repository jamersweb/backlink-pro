<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\SeoAlert;
use App\Services\SEO\AnomalyDetector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DetectSeoAnomaliesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;
    public $queue = 'alerts';

    public function __construct(
        public int $organizationId,
        public ?string $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday()->toDateString();
    }

    public function handle(): void
    {
        $organization = Organization::find($this->organizationId);
        if (!$organization) {
            return;
        }

        $detector = new AnomalyDetector();
        $alerts = $detector->detectAnomalies($organization, $this->date);

        foreach ($alerts as $alertData) {
            $alert = SeoAlert::create([
                'organization_id' => $this->organizationId,
                'rule_id' => $alertData['rule_id'] ?? null,
                'severity' => $alertData['severity'],
                'title' => $alertData['title'],
                'message' => $alertData['message'],
                'diff' => $alertData['diff'] ?? null,
                'related_date' => $alertData['related_date'] ?? $this->date,
            ]);

            // Send notifications
            $this->sendNotifications($alert, $organization);
        }

        Log::info("SEO anomaly detection completed", [
            'organization_id' => $this->organizationId,
            'date' => $this->date,
            'alerts_count' => count($alerts),
        ]);
    }

    protected function sendNotifications(SeoAlert $alert, Organization $organization): void
    {
        $rule = $alert->rule;
        $emails = $rule?->notify_emails ?? [];
        
        if (empty($emails)) {
            // Fallback to org billing email
            if ($organization->billing_email) {
                $emails = [$organization->billing_email];
            } else {
                $owner = $organization->owner;
                if ($owner) {
                    $emails = [$owner->email];
                }
            }
        }

        foreach ($emails as $email) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\SeoAlertMail($alert, $organization));
            } catch (\Exception $e) {
                \Log::warning("Failed to send SEO alert email", [
                    'email' => $email,
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $alert->update(['sent_at' => now()]);
    }
}

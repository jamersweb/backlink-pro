<?php

namespace App\Services\Monitoring;

use App\Models\AuditAlert;
use App\Models\AuditMonitor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class AlertNotifier
{
    /**
     * Send alert notifications
     */
    public function send(AuditAlert $alert, AuditMonitor $monitor): void
    {
        // Send email notifications
        if ($monitor->notify_emails) {
            $this->sendEmails($alert, $monitor);
        }

        // Send Slack notification
        if ($monitor->slack_webhook_url_encrypted) {
            $this->sendSlackNotification($alert, $monitor);
        }

        $alert->update(['sent_at' => now()]);
    }

    /**
     * Send email notifications
     */
    protected function sendEmails(AuditAlert $alert, AuditMonitor $monitor): void
    {
        foreach ($monitor->notify_emails as $email) {
            try {
                Mail::to($email)->send(new \App\Mail\AuditAlertMail($alert, $monitor));
            } catch (\Exception $e) {
                Log::warning("Failed to send alert email", [
                    'alert_id' => $alert->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send Slack notification
     */
    protected function sendSlackNotification(AuditAlert $alert, AuditMonitor $monitor): void
    {
        try {
            $webhookUrl = Crypt::decryptString($monitor->slack_webhook_url_encrypted);
            
            $severityColors = [
                'info' => '#36a64f',
                'warning' => '#ffa500',
                'critical' => '#ff0000',
            ];

            $payload = [
                'attachments' => [
                    [
                        'color' => $severityColors[$alert->severity] ?? '#808080',
                        'title' => $alert->title,
                        'text' => $alert->message,
                        'fields' => [
                            [
                                'title' => 'Monitor',
                                'value' => $monitor->name,
                                'short' => true,
                            ],
                            [
                                'title' => 'Severity',
                                'value' => ucfirst($alert->severity),
                                'short' => true,
                            ],
                        ],
                        'footer' => 'BacklinkPro Monitoring',
                        'ts' => $alert->created_at->timestamp,
                    ],
                ],
            ];

            Http::post($webhookUrl, $payload);

        } catch (\Exception $e) {
            Log::warning("Failed to send Slack notification", [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

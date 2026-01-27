<?php

namespace App\Jobs\Notifications;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public int $notificationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $notification = Notification::with('user')->findOrFail($this->notificationId);
            $user = $notification->user;

            if (!$user || !$user->email) {
                Log::warning('Cannot send notification email: user or email missing', [
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            // Check if user has email notifications enabled
            $emailPreference = $user->notification_preferences['email'] ?? true;
            if (!$emailPreference) {
                Log::info('User has email notifications disabled', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            // Build email content based on notification type
            $subject = $this->getEmailSubject($notification);
            $content = $this->getEmailContent($notification);

            // Send the email
            Mail::send([], [], function ($message) use ($user, $subject, $content) {
                $message->to($user->email, $user->name)
                    ->subject($subject)
                    ->html($content);
            });

            // Mark notification as emailed
            $notification->update([
                'email_sent_at' => now(),
            ]);

            Log::info('Notification email sent', [
                'notification_id' => $this->notificationId,
                'user_id' => $user->id,
                'type' => $notification->type,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notification email', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Get email subject based on notification type
     */
    protected function getEmailSubject(Notification $notification): string
    {
        $subjects = [
            'campaign_completed' => 'Your campaign has been completed',
            'campaign_paused' => 'Your campaign has been paused',
            'backlink_verified' => 'Backlink verified successfully',
            'backlink_failed' => 'Backlink verification failed',
            'daily_limit_reached' => 'Daily limit reached',
            'system_alert' => 'System Alert',
        ];

        return $subjects[$notification->type] ?? 'BacklinkPro Notification';
    }

    /**
     * Get email content based on notification
     */
    protected function getEmailContent(Notification $notification): string
    {
        $appName = config('app.name', 'BacklinkPro');
        $appUrl = config('app.url');

        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #111;'>{$notification->title}</h2>
                <p>{$notification->message}</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    You're receiving this email from {$appName}. 
                    <a href='{$appUrl}/settings/notifications'>Manage your notification preferences</a>
                </p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationEmailJob permanently failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}

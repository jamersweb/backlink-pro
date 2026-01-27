<?php

namespace App\Jobs\Notifications;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public int $notificationId
    ) {}

    public function handle(): void
    {
        $notification = Notification::findOrFail($this->notificationId);
        $user = $notification->user;

        // Check if user wants instant emails (for MVP, skip - use digest only)
        // This job can be used for instant emails if enabled in user settings
        // For now, this is a placeholder - actual email sending will be in digest

        // TODO: Send instant email if user setting allows
    }
}

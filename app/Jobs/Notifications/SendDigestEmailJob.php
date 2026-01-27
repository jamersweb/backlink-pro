<?php

namespace App\Jobs\Notifications;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDigestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public int $userId,
        public string $period // 'daily' or 'weekly'
    ) {}

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        $startDate = $this->period === 'daily'
            ? now()->subDay()
            : now()->subWeek();

        // Get unread critical/warning notifications
        $notifications = Notification::where('user_id', $user->id)
            ->whereIn('severity', ['critical', 'warning'])
            ->where('status', Notification::STATUS_UNREAD)
            ->where('created_at', '>=', $startDate)
            ->where('muted', false)
            ->with('domain')
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('domain_id');

        if ($notifications->isEmpty()) {
            return; // No notifications to send
        }

        // TODO: Create email template and send
        // For MVP, just log
        \Log::info("Digest email would be sent to user {$user->id}", [
            'period' => $this->period,
            'notifications_count' => $notifications->flatten()->count(),
        ]);
    }
}

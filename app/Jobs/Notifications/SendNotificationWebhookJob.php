<?php

namespace App\Jobs\Notifications;

use App\Models\Notification;
use App\Models\NotificationEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];
    public $timeout = 30;

    public function __construct(
        public int $notificationId,
        public int $endpointId
    ) {}

    public function handle(): void
    {
        $notification = Notification::findOrFail($this->notificationId);
        $endpoint = NotificationEndpoint::findOrFail($this->endpointId);

        if (!$endpoint->is_active) {
            return;
        }

        $payload = [
            'id' => $notification->id,
            'type' => $notification->type,
            'domain' => $notification->domain?->name,
            'severity' => $notification->severity,
            'title' => $notification->title,
            'message' => $notification->message,
            'action_url' => $notification->action_url,
            'evidence' => $notification->evidence_json,
            'created_at' => $notification->created_at->toISOString(),
        ];

        $body = json_encode($payload);

        // Sign payload if secret exists
        $headers = ['Content-Type' => 'application/json'];
        if ($endpoint->secret) {
            $signature = hash_hmac('sha256', $body, $endpoint->secret);
            $headers['X-BacklinkPro-Signature'] = $signature;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($endpoint->url, $payload);

            if (!$response->successful()) {
                throw new \Exception("Webhook returned status {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'endpoint_id' => $endpoint->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            // Log to System Health if repeated failures
            if ($this->attempts() >= 3) {
                // TODO: Log to System Health
            }

            throw $e; // Retry
        }
    }
}

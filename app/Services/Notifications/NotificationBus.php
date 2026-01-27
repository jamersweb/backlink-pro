<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\Domain;
use App\Models\Notification;
use App\Models\NotificationRule;
use App\Models\NotificationEndpoint;
use App\Jobs\Notifications\SendNotificationEmailJob;
use App\Jobs\Notifications\SendNotificationWebhookJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class NotificationBus
{
    /**
     * Emit a notification
     */
    public function emit(User $user, ?Domain $domain, string $type, array $payload): ?Notification
    {
        // 1. Resolve rule (domain-specific first, else global, else defaults)
        $rule = $this->resolveRule($user, $domain, $type);

        // 2. If rule disabled -> ignore
        if (!$rule || !$rule->is_enabled) {
            return null;
        }

        // 3. Compute fingerprint
        $fingerprint = $this->computeFingerprint($user->id, $domain?->id, $type, $payload);

        // 4. Check cooldown
        if ($this->isInCooldown($user->id, $fingerprint, $rule->cooldown_minutes)) {
            return null;
        }

        // 5. Create notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'domain_id' => $domain?->id,
            'type' => $type,
            'title' => $payload['title'] ?? $this->getDefaultTitle($type),
            'message' => $payload['message'] ?? $this->getDefaultMessage($type, $payload),
            'severity' => $rule->severity,
            'action_url' => $payload['action_url'] ?? null,
            'evidence_json' => $payload['evidence'] ?? null,
            'fingerprint' => $fingerprint,
            'status' => Notification::STATUS_UNREAD,
        ]);

        // 6. Dispatch delivery jobs based on channels
        $channels = $rule->channels_json ?? ['in_app' => true];

        if ($channels['email'] ?? false) {
            SendNotificationEmailJob::dispatch($notification->id);
        }

        if ($channels['webhook'] ?? false) {
            $endpoints = NotificationEndpoint::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();
            foreach ($endpoints as $endpoint) {
                SendNotificationWebhookJob::dispatch($notification->id, $endpoint->id);
            }
        }

        // Clear badge cache
        Cache::forget("notification_count_{$user->id}");

        return $notification;
    }

    /**
     * Resolve notification rule
     */
    protected function resolveRule(User $user, ?Domain $domain, string $type): ?NotificationRule
    {
        // Try domain-specific first
        if ($domain) {
            $rule = NotificationRule::where('user_id', $user->id)
                ->where('domain_id', $domain->id)
                ->where('type', $type)
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // Try global (domain_id = null)
        $rule = NotificationRule::where('user_id', $user->id)
            ->whereNull('domain_id')
            ->where('type', $type)
            ->first();
        if ($rule) {
            return $rule;
        }

        // Return default rule (create on-the-fly, don't persist)
        return $this->getDefaultRule($type);
    }

    /**
     * Get default rule (not persisted)
     */
    protected function getDefaultRule(string $type): NotificationRule
    {
        $defaults = [
            Notification::TYPE_RANK_DROP => [
                'cooldown_minutes' => 1440, // 24 hours
                'severity' => Notification::SEVERITY_WARNING,
                'channels_json' => ['in_app' => true, 'email' => false, 'webhook' => false],
            ],
            Notification::TYPE_TOXIC_SPIKE => [
                'cooldown_minutes' => 720, // 12 hours
                'severity' => Notification::SEVERITY_WARNING,
                'channels_json' => ['in_app' => true, 'email' => false, 'webhook' => false],
            ],
            Notification::TYPE_AUDIT_CRITICAL => [
                'cooldown_minutes' => 720,
                'severity' => Notification::SEVERITY_CRITICAL,
                'channels_json' => ['in_app' => true, 'email' => true, 'webhook' => false],
            ],
            Notification::TYPE_GOOGLE_DISCONNECT => [
                'cooldown_minutes' => 1440,
                'severity' => Notification::SEVERITY_CRITICAL,
                'channels_json' => ['in_app' => true, 'email' => true, 'webhook' => false],
            ],
            Notification::TYPE_BACKLINKS_LOST_SPIKE => [
                'cooldown_minutes' => 1440,
                'severity' => Notification::SEVERITY_WARNING,
                'channels_json' => ['in_app' => true, 'email' => false, 'webhook' => false],
            ],
            Notification::TYPE_META_PUBLISH_FAILED => [
                'cooldown_minutes' => 60,
                'severity' => Notification::SEVERITY_WARNING,
                'channels_json' => ['in_app' => true, 'email' => false, 'webhook' => false],
            ],
            Notification::TYPE_QUOTA_LIMIT => [
                'cooldown_minutes' => 720,
                'severity' => Notification::SEVERITY_CRITICAL,
                'channels_json' => ['in_app' => true, 'email' => true, 'webhook' => false],
            ],
        ];

        $config = $defaults[$type] ?? [
            'cooldown_minutes' => 720,
            'severity' => Notification::SEVERITY_INFO,
            'channels_json' => ['in_app' => true, 'email' => false, 'webhook' => false],
        ];

        $rule = new NotificationRule();
        $rule->is_enabled = true;
        $rule->cooldown_minutes = $config['cooldown_minutes'];
        $rule->severity = $config['severity'];
        $rule->channels_json = $config['channels_json'];
        $rule->thresholds_json = [];

        return $rule;
    }

    /**
     * Compute fingerprint for deduplication
     */
    protected function computeFingerprint(int $userId, ?int $domainId, string $type, array $payload): string
    {
        // Extract key from payload (keyword, ref_domain, audit_id, etc.)
        $key = $this->extractKey($type, $payload);
        $data = "{$userId}|{$domainId}|{$type}|{$key}";
        return hash('sha256', $data);
    }

    /**
     * Extract key from payload for fingerprinting
     */
    protected function extractKey(string $type, array $payload): string
    {
        return match($type) {
            Notification::TYPE_RANK_DROP => $payload['evidence']['keyword'] ?? 'unknown',
            Notification::TYPE_TOXIC_SPIKE => 'toxic',
            Notification::TYPE_AUDIT_CRITICAL => (string)($payload['evidence']['audit_id'] ?? 'unknown'),
            Notification::TYPE_GOOGLE_DISCONNECT => $payload['evidence']['provider'] ?? 'google',
            Notification::TYPE_BACKLINKS_LOST_SPIKE => 'lost_spike',
            Notification::TYPE_META_PUBLISH_FAILED => $payload['evidence']['domain_id'] ?? 'unknown',
            Notification::TYPE_QUOTA_LIMIT => $payload['evidence']['quota_key'] ?? 'unknown',
            default => 'default',
        };
    }

    /**
     * Check if notification is in cooldown period
     */
    protected function isInCooldown(int $userId, string $fingerprint, int $cooldownMinutes): bool
    {
        $cutoff = now()->subMinutes($cooldownMinutes);

        return Notification::where('user_id', $userId)
            ->where('fingerprint', $fingerprint)
            ->where('created_at', '>=', $cutoff)
            ->exists();
    }

    /**
     * Get default title for type
     */
    protected function getDefaultTitle(string $type): string
    {
        return match($type) {
            Notification::TYPE_RANK_DROP => 'Ranking Drop Detected',
            Notification::TYPE_TOXIC_SPIKE => 'Toxic Backlinks Spike',
            Notification::TYPE_AUDIT_CRITICAL => 'Critical Audit Issues Found',
            Notification::TYPE_GOOGLE_DISCONNECT => 'Google Integration Disconnected',
            Notification::TYPE_BACKLINKS_LOST_SPIKE => 'Backlinks Lost Spike',
            Notification::TYPE_META_PUBLISH_FAILED => 'Meta Publish Failures',
            Notification::TYPE_QUOTA_LIMIT => 'Quota Limit Reached',
            default => 'Notification',
        };
    }

    /**
     * Get default message for type
     */
    protected function getDefaultMessage(string $type, array $payload): string
    {
        $evidence = $payload['evidence'] ?? [];
        return match($type) {
            Notification::TYPE_RANK_DROP => "Keyword '{$evidence['keyword']}' dropped from position {$evidence['old_pos']} to {$evidence['new_pos']}.",
            Notification::TYPE_TOXIC_SPIKE => "Toxic backlinks percentage increased significantly.",
            Notification::TYPE_AUDIT_CRITICAL => "Audit found {$evidence['critical_count']} critical issues.",
            Notification::TYPE_GOOGLE_DISCONNECT => "Google {$evidence['provider']} integration disconnected. Please reconnect.",
            Notification::TYPE_BACKLINKS_LOST_SPIKE => "Lost {$evidence['lost_count']} backlinks in the last 7 days.",
            Notification::TYPE_META_PUBLISH_FAILED => "Multiple meta publish failures detected.",
            Notification::TYPE_QUOTA_LIMIT => "You've reached {$evidence['usage_percent']}% of your quota limit.",
            default => 'Notification',
        };
    }
}



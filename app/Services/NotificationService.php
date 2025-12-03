<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * Send a notification to a user
     */
    public static function notify(
        User|int $user,
        string $type,
        string $title,
        string $message,
        ?Model $subject = null,
        ?array $data = null
    ): UserNotification {
        $userId = $user instanceof User ? $user->id : $user;
        
        return UserNotification::createNotification(
            $userId,
            $type,
            $title,
            $message,
            $subject,
            $data
        );
    }

    /**
     * Notify about campaign status
     */
    public static function notifyCampaignCreated(Model $campaign): void
    {
        self::notify(
            $campaign->user_id,
            UserNotification::TYPE_SUCCESS,
            'Campaign Created',
            "Your campaign '{$campaign->name}' has been created successfully.",
            $campaign,
            ['campaign_id' => $campaign->id, 'campaign_name' => $campaign->name]
        );
    }

    public static function notifyCampaignPaused(Model $campaign): void
    {
        self::notify(
            $campaign->user_id,
            UserNotification::TYPE_WARNING,
            'Campaign Paused',
            "Your campaign '{$campaign->name}' has been paused.",
            $campaign,
            ['campaign_id' => $campaign->id, 'campaign_name' => $campaign->name]
        );
    }

    public static function notifyCampaignResumed(Model $campaign): void
    {
        self::notify(
            $campaign->user_id,
            UserNotification::TYPE_SUCCESS,
            'Campaign Resumed',
            "Your campaign '{$campaign->name}' has been resumed.",
            $campaign,
            ['campaign_id' => $campaign->id, 'campaign_name' => $campaign->name]
        );
    }

    public static function notifyCampaignCompleted(Model $campaign): void
    {
        self::notify(
            $campaign->user_id,
            UserNotification::TYPE_INFO,
            'Campaign Completed',
            "Your campaign '{$campaign->name}' has reached its total limit and been completed.",
            $campaign,
            ['campaign_id' => $campaign->id, 'campaign_name' => $campaign->name]
        );
    }

    /**
     * Notify about backlink status
     */
    public static function notifyBacklinkVerified(Model $backlink): void
    {
        self::notify(
            $backlink->campaign->user_id,
            UserNotification::TYPE_SUCCESS,
            'Backlink Verified',
            "A {$backlink->type} backlink has been verified: {$backlink->url}",
            $backlink,
            [
                'backlink_id' => $backlink->id,
                'backlink_url' => $backlink->url,
                'backlink_type' => $backlink->type,
                'campaign_id' => $backlink->campaign_id,
            ]
        );
    }

    public static function notifyBacklinkFailed(Model $backlink, string $reason = ''): void
    {
        self::notify(
            $backlink->campaign->user_id,
            UserNotification::TYPE_ERROR,
            'Backlink Failed',
            "A {$backlink->type} backlink failed: {$backlink->url}" . ($reason ? " - {$reason}" : ''),
            $backlink,
            [
                'backlink_id' => $backlink->id,
                'backlink_url' => $backlink->url,
                'backlink_type' => $backlink->type,
                'campaign_id' => $backlink->campaign_id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify about plan limits
     */
    public static function notifyDailyLimitReached(User $user, int $limit): void
    {
        self::notify(
            $user,
            UserNotification::TYPE_WARNING,
            'Daily Limit Reached',
            "You have reached your daily backlink limit of {$limit}. Your campaigns will resume tomorrow.",
            null,
            ['daily_limit' => $limit]
        );
    }

    public static function notifyCampaignLimitReached(User $user, int $limit): void
    {
        self::notify(
            $user,
            UserNotification::TYPE_WARNING,
            'Campaign Limit Reached',
            "You have reached your plan's maximum campaign limit of {$limit}. Please upgrade your plan to create more campaigns.",
            null,
            ['max_campaigns' => $limit]
        );
    }

    /**
     * Notify about system alerts
     */
    public static function notifySystemAlert(User $user, string $title, string $message): void
    {
        self::notify(
            $user,
            UserNotification::TYPE_INFO,
            $title,
            $message,
            null
        );
    }
}



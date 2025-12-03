<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Log a campaign activity
     */
    public static function logCampaignCreated(Model $campaign): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CAMPAIGN_CREATED,
            $campaign,
            "Campaign '{$campaign->name}' was created",
            ['campaign_name' => $campaign->name, 'campaign_id' => $campaign->id]
        );
    }

    public static function logCampaignUpdated(Model $campaign, array $changes = []): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CAMPAIGN_UPDATED,
            $campaign,
            "Campaign '{$campaign->name}' was updated",
            ['campaign_name' => $campaign->name, 'campaign_id' => $campaign->id, 'changes' => $changes]
        );
    }

    public static function logCampaignDeleted(Model $campaign): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CAMPAIGN_DELETED,
            null,
            "Campaign '{$campaign->name}' was deleted",
            ['campaign_name' => $campaign->name, 'campaign_id' => $campaign->id]
        );
    }

    public static function logCampaignPaused(Model $campaign): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CAMPAIGN_PAUSED,
            $campaign,
            "Campaign '{$campaign->name}' was paused",
            ['campaign_name' => $campaign->name, 'campaign_id' => $campaign->id]
        );
    }

    public static function logCampaignResumed(Model $campaign): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CAMPAIGN_RESUMED,
            $campaign,
            "Campaign '{$campaign->name}' was resumed",
            ['campaign_name' => $campaign->name, 'campaign_id' => $campaign->id]
        );
    }

    /**
     * Log a backlink activity
     */
    public static function logBacklinkCreated(Model $backlink): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_BACKLINK_CREATED,
            $backlink,
            "New {$backlink->type} backlink created",
            ['backlink_id' => $backlink->id, 'type' => $backlink->type, 'url' => $backlink->url]
        );
    }

    public static function logBacklinkVerified(Model $backlink): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_BACKLINK_VERIFIED,
            $backlink,
            "Backlink verified: {$backlink->url}",
            ['backlink_id' => $backlink->id, 'url' => $backlink->url]
        );
    }

    public static function logBacklinkFailed(Model $backlink, string $reason = ''): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_BACKLINK_FAILED,
            $backlink,
            "Backlink failed: {$backlink->url}",
            ['backlink_id' => $backlink->id, 'url' => $backlink->url, 'reason' => $reason]
        );
    }

    /**
     * Log a domain activity
     */
    public static function logDomainCreated(Model $domain): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DOMAIN_CREATED,
            $domain,
            "Domain '{$domain->name}' was created",
            ['domain_name' => $domain->name, 'domain_id' => $domain->id]
        );
    }

    public static function logDomainUpdated(Model $domain): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DOMAIN_UPDATED,
            $domain,
            "Domain '{$domain->name}' was updated",
            ['domain_name' => $domain->name, 'domain_id' => $domain->id]
        );
    }

    public static function logDomainDeleted(Model $domain): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DOMAIN_DELETED,
            null,
            "Domain '{$domain->name}' was deleted",
            ['domain_name' => $domain->name, 'domain_id' => $domain->id]
        );
    }

    /**
     * Log user activity
     */
    public static function logUserLogin(?int $userId = null): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_USER_LOGIN,
            null,
            'User logged in',
            [],
            $userId
        );
    }

    public static function logUserLogout(?int $userId = null): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_USER_LOGOUT,
            null,
            'User logged out',
            [],
            $userId
        );
    }

    public static function logUserRegistered(Model $user): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_USER_REGISTERED,
            $user,
            "User '{$user->name}' registered",
            ['user_name' => $user->name, 'user_email' => $user->email]
        );
    }
}



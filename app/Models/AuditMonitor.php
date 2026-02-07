<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class AuditMonitor extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'target_url',
        'schedule_rrule',
        'pages_limit',
        'crawl_depth',
        'lighthouse_pages',
        'is_enabled',
        'notify_emails',
        'slack_webhook_url_encrypted',
    ];

    protected $casts = [
        'notify_emails' => 'array',
        'is_enabled' => 'boolean',
        'pages_limit' => 'integer',
        'crawl_depth' => 'integer',
        'lighthouse_pages' => 'integer',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get audits for this monitor
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class, 'monitor_id');
    }

    /**
     * Get alerts for this monitor
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AuditAlert::class);
    }

    /**
     * Get decrypted Slack webhook URL
     */
    public function getSlackWebhookUrlAttribute(): ?string
    {
        if (!$this->slack_webhook_url_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->slack_webhook_url_encrypted);
    }

    /**
     * Set encrypted Slack webhook URL
     */
    public function setSlackWebhookUrlAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['slack_webhook_url_encrypted'] = Crypt::encryptString($value);
        } else {
            $this->attributes['slack_webhook_url_encrypted'] = null;
        }
    }
}

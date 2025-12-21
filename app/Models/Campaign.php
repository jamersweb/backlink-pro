<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'domain_id',
        'name',
        'web_name',
        'web_url',
        'web_keyword',
        'web_about',
        'web_target',
        'country_name',
        'company_name',
        'company_logo',
        'company_email_address',
        'company_address',
        'company_number',
        'company_country',
        'company_state',
        'company_city',
        'gmail',
        'password',
        'gmail_account_id',
        'requires_email_verification',
        'status',
        'settings', // JSON: backlink types, limits, content settings, scheduling
        'start_date',
        'end_date',
        'daily_limit',
        'total_limit',
        'category_id',
        'subcategory_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'requires_email_verification' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Campaign statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'company_country');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'company_state');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'company_city');
    }

    public function gmailAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class, 'gmail_account_id');
    }

    /**
     * Get opportunities (where user's links were added)
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(BacklinkOpportunity::class);
    }

    /**
     * Legacy alias for backlinks - now returns opportunities
     * @deprecated Use opportunities() instead
     */
    public function backlinks(): HasMany
    {
        return $this->opportunities();
    }

    public function siteAccounts(): HasMany
    {
        return $this->hasMany(SiteAccount::class);
    }

    public function automationTasks(): HasMany
    {
        return $this->hasMany(AutomationTask::class);
    }

    /**
     * Get opportunities count for today
     */
    public function getTodayBacklinksCountAttribute(): int
    {
        return $this->opportunities()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Check if campaign is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope for active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'plan_key',
        'plan_status',
        'trial_ends_at',
        'seats_limit',
        'seats_used',
        'billing_email',
        'billing_name',
        'billing_address',
        'pagespeed_api_key_encrypted',
        'pagespeed_byok_enabled',
        'pagespeed_last_key_verified_at',
        'usage_period_started_at',
        'usage_period_ends_at',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'trial_ends_at' => 'datetime',
        'usage_period_started_at' => 'datetime',
        'usage_period_ends_at' => 'datetime',
        'pagespeed_api_key_encrypted' => 'encrypted',
        'pagespeed_byok_enabled' => 'boolean',
        'pagespeed_last_key_verified_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($org) {
            if (empty($org->slug)) {
                $org->slug = Str::slug($org->name);
            }
        });
    }

    /**
     * Get the owner user
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get organization users
     */
    public function users(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Get audits for this organization
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    /**
     * Get branding profile
     */
    public function brandingProfile(): HasOne
    {
        return $this->hasOne(BrandingProfile::class);
    }

    /**
     * Get custom domains
     */
    public function customDomains(): HasMany
    {
        return $this->hasMany(CustomDomain::class);
    }

    /**
     * Get API keys
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get leads
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get usage events
     */
    public function usageEvents(): HasMany
    {
        return $this->hasMany(UsageEvent::class);
    }

    /**
     * Get invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * Get service requests
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get repo connections
     */
    public function repoConnections(): HasMany
    {
        return $this->hasMany(RepoConnection::class);
    }

    /**
     * Get repos
     */
    public function repos(): HasMany
    {
        return $this->hasMany(Repo::class);
    }

    /**
     * Get monitors
     */
    public function monitors(): HasMany
    {
        return $this->hasMany(AuditMonitor::class);
    }

    /**
     * Get OAuth connections
     */
    public function oauthConnections(): HasMany
    {
        return $this->hasMany(OauthConnection::class);
    }

    /**
     * Get GSC sites
     */
    public function gscSites(): HasMany
    {
        return $this->hasMany(GscSite::class);
    }

    /**
     * Get GA4 properties
     */
    public function ga4Properties(): HasMany
    {
        return $this->hasMany(Ga4Property::class);
    }

    /**
     * Get rank projects
     */
    public function rankProjects(): HasMany
    {
        return $this->hasMany(RankProject::class);
    }

    /**
     * Get SEO alerts
     */
    public function seoAlerts(): HasMany
    {
        return $this->hasMany(SeoAlert::class);
    }

    /**
     * Get monthly reports
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * Get plan model
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_key', 'code');
    }

    /**
     * Check if organization has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return in_array($this->plan_status, ['active', 'trialing']) &&
               ($this->usage_period_ends_at === null || $this->usage_period_ends_at->isFuture());
    }

    /**
     * Check if organization is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->plan_status === 'trialing' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if user belongs to this organization
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Get user's role in this organization
     */
    public function getUserRole(User $user): ?string
    {
        $orgUser = $this->users()->where('user_id', $user->id)->first();
        return $orgUser ? $orgUser->role : null;
    }

    /**
     * Check if user can manage organization
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($this->owner_user_id === $user->id) {
            return true;
        }

        $role = $this->getUserRole($user);
        return in_array($role, ['owner', 'admin']);
    }
}

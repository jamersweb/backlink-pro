<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plan_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'trial_ends_at',
        'google_id',
        'apple_id',
        'github_id',
        'microsoft_id',
        'facebook_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_access_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'google_connected_at' => 'datetime',
        ];
    }

    /**
     * Encrypt/decrypt Google tokens
     */
    public function setGoogleAccessTokenAttribute($value): void
    {
        $this->attributes['google_access_token'] = ($value && $value !== '') ? Crypt::encryptString($value) : null;
    }

    public function getGoogleAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setGoogleRefreshTokenAttribute($value): void
    {
        $this->attributes['google_refresh_token'] = ($value && $value !== '') ? Crypt::encryptString($value) : null;
    }

    public function getGoogleRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Relationships
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function connectedAccounts()
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get subscription for quota system
     */
    public function subscription()
    {
        return $this->hasOne(UserSubscription::class);
    }

    /**
     * Get current plan (from subscription or fallback)
     */
    public function currentPlan()
    {
        $subscription = $this->subscription;
        if ($subscription && $subscription->isActive()) {
            return $subscription->plan;
        }
        // Fallback to starter plan
        return \App\Models\Plan::where('code', 'starter')->first();
    }
    public function hasActiveFreeTrial(): bool
    {
        return $this->subscription_status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function startFreeTrialIfEligible(int $days = 7): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        if ($this->trial_ends_at !== null) {
            return false;
        }

        if ($this->stripe_subscription_id || $this->subscription_status === 'active') {
            return false;
        }

        $this->forceFill([
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays($days),
        ])->save();

        return true;
    }

    /**
     * Get plan limits
     */
    public function planLimits(): array
    {
        $plan = $this->currentPlan();
        return $plan ? $plan->limits_json : [];
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(UserNotification::class)->unread();
    }

    /**
     * Get teams this user belongs to
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get team memberships
     */
    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get the primary team (first team where user is owner)
     */
    public function primaryTeam()
    {
        return $this->teams()->wherePivot('role', Team::ROLE_OWNER)->first();
    }

    /**
     * Get total backlinks count
     */
    public function getTotalBacklinksCountAttribute(): int
    {
        return $this->campaigns()
            ->withCount('backlinks')
            ->get()
            ->sum('backlinks_count');
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmail);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    /**
     * Get provider settings
     */
    public function providerSettings()
    {
        return $this->hasMany(UserProviderSetting::class);
    }

    /**
     * Get cost logs
     */
    public function costLogs()
    {
        return $this->hasMany(CrawlCostLog::class);
    }

    /**
     * Get organization memberships
     */
    public function organizationUsers()
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Get organizations this user belongs to
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot('role')
            ->withTimestamps();
    }
}



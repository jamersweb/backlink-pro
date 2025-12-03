<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
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

    public function connectedAccounts()
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
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
}

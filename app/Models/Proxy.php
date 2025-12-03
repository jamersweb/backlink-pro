<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'type', // http, https, socks5
        'country',
        'status',
        'error_count',
        'last_used_at',
        'last_error_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'error_count' => 'integer',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /**
     * Proxy types
     */
    const TYPE_HTTP = 'http';
    const TYPE_HTTPS = 'https';
    const TYPE_SOCKS5 = 'socks5';

    /**
     * Proxy statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_DISABLED = 'disabled';
    const STATUS_BLACKLISTED = 'blacklisted';

    /**
     * Get proxy URL for use in Playwright/requests
     */
    public function getUrlAttribute(): string
    {
        $url = "{$this->type}://";
        
        if ($this->username && $this->password) {
            $url .= "{$this->username}:{$this->password}@";
        }
        
        $url .= "{$this->host}:{$this->port}";
        
        return $url;
    }

    /**
     * Get proxy server (host:port)
     */
    public function getServerAttribute(): string
    {
        return "{$this->host}:{$this->port}";
    }

    /**
     * Increment error count
     */
    public function incrementError(): void
    {
        $this->increment('error_count');
        $this->update(['last_error_at' => now()]);

        // Auto-disable if too many errors
        if ($this->error_count >= 10) {
            $this->update(['status' => self::STATUS_BLACKLISTED]);
        }
    }

    /**
     * Mark proxy as used
     */
    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Reset error count
     */
    public function resetErrors(): void
    {
        $this->update([
            'error_count' => 0,
            'last_error_at' => null,
        ]);
    }

    /**
     * Scope for active proxies
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for proxies by country
     */
    public function scopeForCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for healthy proxies (low error count)
     */
    public function scopeHealthy($query, int $maxErrors = 3)
    {
        return $query->where('error_count', '<', $maxErrors);
    }
}


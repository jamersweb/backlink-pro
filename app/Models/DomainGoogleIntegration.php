<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainGoogleIntegration extends Model
{
    protected $fillable = [
        'domain_id',
        'user_id',
        'connected_account_id',
        'gsc_property',
        'ga4_property_id',
        'status',
        'last_synced_at',
        'last_sync_error',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    const STATUS_CONNECTED = 'connected';
    const STATUS_ERROR = 'error';
    const STATUS_DISCONNECTED = 'disconnected';

    /**
     * Get the domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the connected account
     */
    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    /**
     * Get top pages from GSC (latest snapshot)
     */
    public function getTopPages(int $limit = 10): array
    {
        if (!$this->gsc_property) {
            return [];
        }

        $latestDate = GscTopPage::where('domain_id', $this->domain_id)
            ->max('date');

        if (!$latestDate) {
            return [];
        }

        return GscTopPage::where('domain_id', $this->domain_id)
            ->where('date', $latestDate)
            ->orderBy('clicks', 'desc')
            ->limit($limit)
            ->pluck('page')
            ->toArray();
    }
}

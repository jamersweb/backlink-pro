<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class NotificationEndpoint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set secret (encrypt)
     */
    public function setSecretAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['secret'] = null;
            return;
        }
        $this->attributes['secret'] = Crypt::encryptString($value);
    }

    /**
     * Get secret (decrypt)
     */
    public function getSecretAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

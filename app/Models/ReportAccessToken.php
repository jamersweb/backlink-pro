<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReportAccessToken extends Model
{
    protected $fillable = [
        'audit_id',
        'email',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Generate and store a new token
     */
    public static function generate(Audit $audit, string $email, int $expiresInHours = 24): string
    {
        $rawToken = Str::random(64);
        $tokenHash = Hash::make($rawToken);

        self::create([
            'audit_id' => $audit->id,
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours($expiresInHours),
        ]);

        return $rawToken;
    }

    /**
     * Verify token
     */
    public static function verify(string $rawToken, Audit $audit): ?self
    {
        $tokens = self::where('audit_id', $audit->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->get();

        foreach ($tokens as $token) {
            if (Hash::check($rawToken, $token->token_hash)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}

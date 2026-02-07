<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an action
     */
    public function log(string $action, ?string $targetType = null, ?string $targetId = null, array $meta = []): void
    {
        $user = Auth::user();
        $organization = $user?->currentOrganization ?? null;

        if (!$organization) {
            return; // Can't log without organization
        }

        AuditLog::create([
            'organization_id' => $organization->id,
            'actor_user_id' => $user?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_hash' => $this->hashIp(Request::ip()),
            'user_agent' => Request::userAgent(),
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    /**
     * Hash IP address for privacy
     */
    protected function hashIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // Hash IP for privacy (one-way)
        return hash('sha256', $ip . config('app.key'));
    }
}

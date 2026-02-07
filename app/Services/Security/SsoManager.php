<?php

namespace App\Services\Security;

use App\Models\SsoConnection;
use App\Models\Organization;
use Illuminate\Support\Facades\Crypt;

class SsoManager
{
    /**
     * Create SSO connection
     */
    public function createConnection(Organization $organization, string $type, array $config, ?array $domains = null): SsoConnection
    {
        return SsoConnection::create([
            'organization_id' => $organization->id,
            'type' => $type,
            'config_encrypted' => Crypt::encryptString(json_encode($config)),
            'domains' => $domains,
            'is_enabled' => true,
        ]);
    }

    /**
     * Get decrypted config
     */
    public function getConfig(SsoConnection $connection): array
    {
        return json_decode(Crypt::decryptString($connection->config_encrypted), true);
    }

    /**
     * Check if email domain is allowed
     */
    public function isEmailAllowed(SsoConnection $connection, string $email): bool
    {
        if (empty($connection->domains)) {
            return true; // No domain restriction
        }

        $emailDomain = substr(strrchr($email, "@"), 1);
        
        return in_array($emailDomain, $connection->domains);
    }

    /**
     * Check if SSO is required for organization
     */
    public function isSsoRequired(Organization $organization): bool
    {
        $settings = $organization->securitySettings;
        
        return $settings && $settings->require_sso;
    }
}

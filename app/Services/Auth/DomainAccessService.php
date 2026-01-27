<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Domain;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\DomainAccess;

class DomainAccessService
{
    /**
     * Capability definitions per role
     */
    protected const ROLE_CAPABILITIES = [
        'owner' => [
            'domains.view',
            'domains.manage',
            'analyzer.run',
            'analyzer.view',
            'google.connect',
            'google.sync_now',
            'backlinks.run',
            'backlinks.view',
            'meta.edit',
            'meta.publish',
            'insights.run',
            'insights.view',
            'reports.manage',
            'reports.view',
        ],
        'admin' => [
            'domains.view',
            'domains.manage',
            'analyzer.run',
            'analyzer.view',
            'google.connect',
            'google.sync_now',
            'backlinks.run',
            'backlinks.view',
            'meta.edit',
            'meta.publish',
            'insights.run',
            'insights.view',
            'reports.manage',
            'reports.view',
        ],
        'editor' => [
            'domains.view',
            'analyzer.run',
            'analyzer.view',
            'google.sync_now',
            'backlinks.run',
            'backlinks.view',
            'meta.edit',
            'meta.publish',
            'insights.run',
            'insights.view',
            'reports.manage',
            'reports.view',
        ],
        'viewer' => [
            'domains.view',
            'analyzer.view',
            'backlinks.view',
            'insights.view',
            'reports.view',
        ],
    ];

    /**
     * Check if user has capability for domain
     */
    public function can(User $user, Domain $domain, string $capability): bool
    {
        // Get user's role for this domain
        $role = $this->role($user, $domain);

        if (!$role) {
            return false;
        }

        // Check if role has capability
        $capabilities = self::ROLE_CAPABILITIES[$role] ?? [];
        return in_array($capability, $capabilities);
    }

    /**
     * Get user's role for domain
     */
    public function role(User $user, Domain $domain): ?string
    {
        // If domain has no team, check legacy user_id ownership
        if (!$domain->team_id) {
            if ($domain->user_id === $user->id) {
                return 'owner';
            }
            return null;
        }

        // Check if user is team owner
        $team = $domain->team;
        if ($team->owner_user_id === $user->id) {
            return 'owner';
        }

        // Check team membership
        $member = TeamMember::where('team_id', $domain->team_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return null;
        }

        // Check for domain-specific override
        $domainAccess = DomainAccess::where('domain_id', $domain->id)
            ->where('team_id', $domain->team_id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->first();

        if ($domainAccess) {
            return $domainAccess->role;
        }

        // Return team member role
        return $member->role;
    }

    /**
     * Get all capabilities for user on domain
     */
    public function getCapabilities(User $user, Domain $domain): array
    {
        $role = $this->role($user, $domain);
        if (!$role) {
            return [];
        }

        return self::ROLE_CAPABILITIES[$role] ?? [];
    }

    /**
     * Get abilities object for frontend
     */
    public function getAbilities(User $user, Domain $domain): array
    {
        return [
            'canViewDomain' => $this->can($user, $domain, 'domains.view'),
            'canManageDomain' => $this->can($user, $domain, 'domains.manage'),
            'canRunAudit' => $this->can($user, $domain, 'analyzer.run'),
            'canViewAudit' => $this->can($user, $domain, 'analyzer.view'),
            'canConnectGoogle' => $this->can($user, $domain, 'google.connect'),
            'canSyncNow' => $this->can($user, $domain, 'google.sync_now'),
            'canRunBacklinks' => $this->can($user, $domain, 'backlinks.run'),
            'canViewBacklinks' => $this->can($user, $domain, 'backlinks.view'),
            'canEditMeta' => $this->can($user, $domain, 'meta.edit'),
            'canPublishMeta' => $this->can($user, $domain, 'meta.publish'),
            'canRunInsights' => $this->can($user, $domain, 'insights.run'),
            'canViewInsights' => $this->can($user, $domain, 'insights.view'),
            'canManageReports' => $this->can($user, $domain, 'reports.manage'),
            'canViewReports' => $this->can($user, $domain, 'reports.view'),
            'role' => $this->role($user, $domain),
        ];
    }

    /**
     * Ensure domain has team access record
     */
    public function ensureDomainAccess(Domain $domain): void
    {
        if (!$domain->team_id) {
            return;
        }

        // Check if access record exists
        $exists = DomainAccess::where('domain_id', $domain->id)
            ->where('team_id', $domain->team_id)
            ->exists();

        if (!$exists) {
            // Create default team-wide access (no user_id = team-wide)
            DomainAccess::create([
                'domain_id' => $domain->id,
                'team_id' => $domain->team_id,
                'user_id' => null,
                'role' => 'admin', // Default to admin for team-wide
            ]);
        }
    }
}



<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Check if user can view organization
     */
    public function view(User $user, Organization $organization): bool
    {
        return $organization->hasUser($user);
    }

    /**
     * Check if user can manage organization (owner/admin)
     */
    public function manage(User $user, Organization $organization): bool
    {
        return $organization->canBeManagedBy($user);
    }

    /**
     * Check if user can update organization settings
     */
    public function update(User $user, Organization $organization): bool
    {
        return $organization->canBeManagedBy($user);
    }

    /**
     * Check if user can delete organization
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $organization->owner_user_id === $user->id;
    }
}

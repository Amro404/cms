<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only allow admins to assign roles.
     */
    public function assignRole(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin');
    }

    /**
     * Only allow admins to give permissions.
     */
    public function givePermission(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin');
    }
}

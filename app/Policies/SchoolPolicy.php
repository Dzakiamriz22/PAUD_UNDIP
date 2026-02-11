<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view any schools.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_school');
    }

    /**
     * Determine whether the user can view the school.
     */
    public function view(User $user, School $school): bool
    {
        return $user->hasPermissionTo('view_school');
    }

    /**
     * Determine whether the user can create schools.
     */
    public function create(User $user): bool
    {
        // Only admin and super_admin can create
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the school.
     */
    public function update(User $user, School $school): bool
    {
        // Only admin and super_admin can update
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the school.
     */
    public function delete(User $user, School $school): bool
    {
        // Only super_admin can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the school.
     */
    public function restore(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the school.
     */
    public function forceDelete(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }
}

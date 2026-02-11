<?php

namespace App\Policies;

use App\Models\VirtualAccount;
use App\Models\User;

class VirtualAccountPolicy
{
    /**
     * Determine whether the user can view any virtual accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_virtual_account');
    }

    /**
     * Determine whether the user can view the virtual account.
     */
    public function view(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->hasPermissionTo('view_virtual_account');
    }

    /**
     * Determine whether the user can create virtual accounts.
     */
    public function create(User $user): bool
    {
        // Only bendahara, admin, and kepsek can create
        return $user->isSuperAdmin() || $user->hasRole('admin') || $user->hasRole('bendahara') || $user->isKepsek();
    }

    /**
     * Determine whether the user can update the virtual account.
     */
    public function update(User $user, VirtualAccount $virtualAccount): bool
    {
        // Only bendahara, admin, and kepsek can update
        return $user->isSuperAdmin() || $user->hasRole('admin') || $user->hasRole('bendahara') || $user->isKepsek();
    }

    /**
     * Determine whether the user can delete the virtual account.
     */
    public function delete(User $user, VirtualAccount $virtualAccount): bool
    {
        // Only admin can delete
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the virtual account.
     */
    public function restore(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the virtual account.
     */
    public function forceDelete(User $user, VirtualAccount $virtualAccount): bool
    {
        return $user->isSuperAdmin();
    }
}

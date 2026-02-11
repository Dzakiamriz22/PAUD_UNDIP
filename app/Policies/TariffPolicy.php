<?php

namespace App\Policies;

use App\Models\Tariff;
use App\Models\User;

class TariffPolicy
{
    /**
     * Determine whether the user can view any tariffs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_tariff');
    }

    /**
     * Determine whether the user can view the tariff.
     */
    public function view(User $user, Tariff $tariff): bool
    {
        return $user->hasPermissionTo('view_tariff');
    }

    /**
     * Determine whether the user can create tariffs.
     */
    public function create(User $user): bool
    {
        // Only bendahara, admin, and kepsek can create
        return $user->isSuperAdmin() || $user->hasRole('admin') || $user->hasRole('bendahara') || $user->isKepsek();
    }

    /**
     * Determine whether the user can update the tariff.
     */
    public function update(User $user, Tariff $tariff): bool
    {
        // Only bendahara, admin, and kepsek can update
        return $user->isSuperAdmin() || $user->hasRole('admin') || $user->hasRole('bendahara') || $user->isKepsek();
    }

    /**
     * Determine whether the user can delete the tariff.
     */
    public function delete(User $user, Tariff $tariff): bool
    {
        // Only admin can delete
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the tariff.
     */
    public function restore(User $user, Tariff $tariff): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the tariff.
     */
    public function forceDelete(User $user, Tariff $tariff): bool
    {
        return $user->isSuperAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolClassPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super roles dan auditor dapat melihat semua
        if ($user->isSuperAdmin() || $user->hasRole('admin') || $user->hasRole('auditor')) {
            return true;
        }

        return $user->hasPermissionTo('view_any_school_class');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SchoolClass $schoolClass): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('view_school_class')) {
            return false;
        }

        // Super roles → allow all
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return true;
        }

        // Auditor → allow all (read-only)
        if ($user->hasRole('auditor')) {
            return true;
        }

        // Guru → only their own class
        if ($user->isGuru()) {
            return $schoolClass->homeroom_teacher_id === $user->id;
        }

        // Bendahara, Kepsek, Operator → allow all
        if ($user->hasAnyRole(['bendahara', 'kepala_sekolah', 'operator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and super_admin can create
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SchoolClass $schoolClass): bool
    {
        // Only admin and super_admin can update
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        // Only super_admin can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isSuperAdmin();
    }
}

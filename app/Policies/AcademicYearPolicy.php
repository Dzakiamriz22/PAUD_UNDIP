<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    /**
     * Determine whether the user can view any academic years.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_academic_year');
    }

    /**
     * Determine whether the user can view the academic year.
     */
    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('view_academic_year');
    }

    /**
     * Determine whether the user can create academic years.
     */
    public function create(User $user): bool
    {
        // Only admin and super_admin can create
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the academic year.
     */
    public function update(User $user, AcademicYear $academicYear): bool
    {
        // Only admin and super_admin can update
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the academic year.
     */
    public function delete(User $user, AcademicYear $academicYear): bool
    {
        // Only super_admin can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the academic year.
     */
    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the academic year.
     */
    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return $user->isSuperAdmin();
    }
}

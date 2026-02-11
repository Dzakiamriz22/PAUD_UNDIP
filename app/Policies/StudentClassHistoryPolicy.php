<?php

namespace App\Policies;

use App\Models\StudentClassHistory;
use App\Models\User;

class StudentClassHistoryPolicy
{
    /**
     * Determine whether the user can view any records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_student_class_history');
    }

    /**
     * Determine whether the user can view the record.
     */
    public function view(User $user, StudentClassHistory $history): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('view_student_class_history')) {
            return false;
        }

        // Super roles → allow all
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return true;
        }

        // Guru → only their class students
        if ($user->isGuru()) {
            return $history->classRoom->homeroom_teacher_id === $user->id;
        }

        // Auditor, Bendahara, Kepsek → allow all
        if ($user->hasAnyRole(['auditor', 'bendahara', 'kepala_sekolah'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create records.
     */
    public function create(User $user): bool
    {
        // Only admin and super_admin can create
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the record.
     */
    public function update(User $user, StudentClassHistory $history): bool
    {
        // Only admin can update
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the record.
     */
    public function delete(User $user, StudentClassHistory $history): bool
    {
        // Only super_admin can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the record.
     */
    public function restore(User $user, StudentClassHistory $history): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the record.
     */
    public function forceDelete(User $user, StudentClassHistory $history): bool
    {
        return $user->isSuperAdmin();
    }
}

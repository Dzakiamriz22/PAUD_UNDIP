<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
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

        return $user->hasPermissionTo('view_any_student');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('view_student')) {
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

        // Guru → only students in their class
        if ($user->isGuru()) {
            return $student->classHistories()
                ->where('is_active', true)
                ->whereHas('classRoom', function ($query) use ($user) {
                    $query->where('homeroom_teacher_id', $user->id);
                })
                ->exists();
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
    public function update(User $user, Student $student): bool
    {
        // Only admin and super_admin can update
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        // Only super_admin can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return $user->isSuperAdmin();
    }
}

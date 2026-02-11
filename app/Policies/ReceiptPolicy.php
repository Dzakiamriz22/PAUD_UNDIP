<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Receipt;

class ReceiptPolicy
{
    /**
     * Determine whether the user can view any receipts.
     */
    public function viewAny(User $user): bool
    {
        // Admin dan roles keuangan dapat melihat semua
        if ($user->is_admin || $user->hasRole('bendahara') || $user->isKepsek()) {
            return true;
        }

        // Auditor dapat melihat semua (read-only)
        if ($user->hasRole('auditor')) {
            return true;
        }

        // Guru hanya bisa melihat (read-only)
        if ($user->hasPermissionTo('view_any_receipt')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the receipt.
     */
    public function view(User $user, Receipt $receipt): bool
    {
        // Admin dapat melihat semua
        if ($user->is_admin) {
            return true;
        }

        // Bendahara dan Kepala Sekolah dapat melihat semua
        if ($user->hasRole('bendahara') || $user->isKepsek()) {
            return true;
        }

        // Auditor dapat melihat semua (read-only)
        if ($user->hasRole('auditor') && $user->hasPermissionTo('view_receipt')) {
            return true;
        }

        // Guru hanya dapat melihat receipt dari siswa di kelasnya (read-only)
        if ($user->isGuru() && $user->hasPermissionTo('view_receipt')) {
            // Melalui invoice -> student
            return $receipt->invoice()
                ->whereHas('student', function ($query) use ($user) {
                    $query->whereHas('classHistories', function ($q) use ($user) {
                        $q->where('is_active', true)
                            ->whereHas('classRoom', function ($c) use ($user) {
                                $c->where('homeroom_teacher_id', $user->id);
                            });
                    });
                })
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create receipts.
     */
    public function create(User $user): bool
    {
        // Only bendahara, admin, dan kepsek dapat membuat
        return $user->is_admin || $user->hasRole('bendahara') || $user->isKepsek();
    }

    /**
     * Determine whether the user can update the receipt.
     */
    public function update(User $user, Receipt $receipt): bool
    {
        // Only bendahara dan admin dapat update
        return $user->is_admin || $user->hasRole('bendahara');
    }

    /**
     * Determine whether the user can delete the receipt.
     */
    public function delete(User $user, Receipt $receipt): bool
    {
        // Only admin dapat delete
        return $user->is_admin;
    }

    /**
     * Determine whether the user can restore the receipt.
     */
    public function restore(User $user, Receipt $receipt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the receipt.
     */
    public function forceDelete(User $user, Receipt $receipt): bool
    {
        return false;
    }
}

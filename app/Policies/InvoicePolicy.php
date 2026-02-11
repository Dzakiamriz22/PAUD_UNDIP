<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
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
        if ($user->hasPermissionTo('view_any_invoice')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Invoice $invoice): bool
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
        if ($user->hasRole('auditor') && $user->hasPermissionTo('view_invoice')) {
            return true;
        }

        // Guru hanya dapat melihat invoice siswa di kelasnya (read-only)
        if ($user->isGuru() && $user->hasPermissionTo('view_invoice')) {
            return $invoice->student()
                ->whereHas('classHistories', function ($query) use ($user) {
                    $query->where('is_active', true)
                        ->whereHas('classRoom', function ($c) use ($user) {
                            $c->where('homeroom_teacher_id', $user->id);
                        });
                })
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only bendahara, admin, dan kepsek dapat membuat
        return $user->is_admin || $user->hasRole('bendahara') || $user->isKepsek();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        // Only bendahara, admin dapat update (dan hanya draft)
        return ($user->is_admin || $user->hasRole('bendahara') || $user->isKepsek()) && $invoice->status === 'draft';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        // Only bendahara dan admin dapat delete (dan hanya draft)
        return ($user->is_admin || $user->hasRole('bendahara')) && $invoice->status === 'draft';
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->is_admin && $invoice->status === 'draft';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->is_admin && $invoice->status === 'draft';
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
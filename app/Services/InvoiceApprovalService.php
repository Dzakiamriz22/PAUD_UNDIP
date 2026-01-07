<?php

namespace App\Services;

use App\Enums\InvoiceApprovalStatusEnum;
use App\Models\Invoice;
use App\Models\InvoiceApprovalLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceApprovalService
{
    /**
     * Get all children unit IDs recursively for given unit IDs.
     */
    protected function getAllChildrenUnitIds(array $unitIds): \Illuminate\Support\Collection
    {
        $allUnitIds = collect($unitIds);

        // Get direct children
        $children = Unit::whereIn('parent_id', $unitIds)->pluck('id');

        if ($children->isNotEmpty()) {
            // Recursively get children of children
            $grandChildren = $this->getAllChildrenUnitIds($children->toArray());
            $allUnitIds = $allUnitIds->merge($children)->merge($grandChildren);
        }

        return $allUnitIds->unique()->values();
    }

    /**
     * Check if payment method is virtual account (payment_code = "01").
     * If payment_code is "01", skip WR2 step.
     */
    protected function isVirtualAccount(Invoice $invoice): bool
    {
        $paymentMethod = $invoice->paymentMethod();
        if (!$paymentMethod) {
            return false;
        }

        // Check payment_code instead of id
        // If payment_code is "01", it's Virtual Account (skip WR2)
        return $paymentMethod->payment_code === '01';
    }

    /**
     * Submit invoice for approval by kasir_unit.
     */
    public function submit(Invoice $invoice, User $user, string $roleName, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            $isVirtualAccount = $this->isVirtualAccount($invoice);
            // Submit to PENDING_KASIR_UNIT
            $invoice->approval_status = $invoice->approval_status->getNextStatus($isVirtualAccount);
            $invoice->submitted_by = $user->id;
            $invoice->submitted_at = now();
            $invoice->is_fully_approved = false;

            $invoice->save();

            // Create approval log
            $this->createApprovalLog($invoice, $invoice->approval_status, 'submit', $user, $roleName, $notes);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve invoice by current approver.
     */
    public function approve(Invoice $invoice, User $user, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            $currentStatus = $invoice->approval_status ?? InvoiceApprovalStatusEnum::DRAFT;
            $bookingOrder = $invoice->settlement->bookingOrder;
            $recipientStatusCode = $bookingOrder->recipientStatus->status_code ?? null;

            // Special case: If booking order has recipientStatusCode = '011',
            // kasir_unit can directly approve from DRAFT status (no approval process needed)
            if ($recipientStatusCode === '011') {
                // Directly approve (no approval process needed)
                $invoice->approval_status = InvoiceApprovalStatusEnum::APPROVED;
                $invoice->is_fully_approved = true;
                $invoice->fully_approved_by = $user->id;
                $invoice->fully_approved_at = now();

                $invoice->invoice_number = Invoice::generateInvoiceNumber($invoice);

                $invoice->save();

                // Create approval log
                $this->createApprovalLog($invoice, InvoiceApprovalStatusEnum::APPROVED, 'approve', $user, 'operator_unit', $notes);

                DB::commit();
                return;
            }

            // Get user role for logging
            $userRole = $this->getUserApprovalRole($invoice, $user);

            // Determine if virtual account
            $isVirtualAccount = $this->isVirtualAccount($invoice);

            // Get next status based on payment method
            $nextStatus = $currentStatus->getNextStatus($isVirtualAccount);

            if (!$nextStatus) {
                throw new \Exception('No next status available');
            }

            // Update invoice status
            $invoice->approval_status = $nextStatus;

            // Determine if fully approved
            if ($nextStatus === InvoiceApprovalStatusEnum::APPROVED) {

                $invoice->invoice_number = Invoice::generateInvoiceNumber($invoice);

                $invoice->is_fully_approved = true;
                $invoice->fully_approved_by = $user->id;
                $invoice->fully_approved_at = now();
            } else {
                $invoice->is_fully_approved = false;
            }

            $invoice->save();

            // Create approval log
            $this->createApprovalLog($invoice, $nextStatus, 'approve', $user, $userRole, $notes);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject invoice by current approver.
     */
    public function reject(Invoice $invoice, User $user, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            // Get user role for logging
            $userRole = $this->getUserApprovalRole($invoice, $user);

            // Reject invoice
            $invoice->approval_status = InvoiceApprovalStatusEnum::DRAFT;
            $invoice->is_fully_approved = false;
            $invoice->fully_approved_by = null;
            $invoice->fully_approved_at = null;

            $invoice->save();

            // Create approval log
            $this->createApprovalLog($invoice, InvoiceApprovalStatusEnum::REJECTED, 'reject', $user, $userRole, $notes ?? 'Invoice rejected.');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if user can submit invoice.
     */
    public function canUserSubmit(Invoice $invoice, User $user): bool
    {
        // Get booking order
        $bookingOrder = $invoice->settlement->bookingOrder;

        // Check if booking order has recipient status code
        $recipientStatusCode = $bookingOrder->recipientStatus->status_code ?? null;

        // If booking order has recipientStatusCode = '011', invoice cannot be submitted
        // because it should be directly approved or rejected (no approval process needed)
        if ($recipientStatusCode === '011') {
            return false;
        }

        // Check if invoice is in draft or rejected status
        $approvalStatus = $invoice->approval_status ?? InvoiceApprovalStatusEnum::DRAFT;
        if (!in_array($approvalStatus, [
            InvoiceApprovalStatusEnum::DRAFT,
        ])) {
            return false;
        }

        $canSubmitRoles = $approvalStatus->getCanSubmitRoles();

        // Only operator_unit or kasir_unit can submit
        if (!$user->hasAnyRole($canSubmitRoles)) {
            return false;
        }

        // Check each role that user has
        foreach ($user->roles as $role) {
            // Only check for operator_unit and kasir_unit roles
            if (!in_array($role->name, $canSubmitRoles)) {
                continue;
            }

            // Get scopes for this role
            $userScopes = $user->getRoleScopeObjects($role->name, Unit::class);
            $scopedUnitIds = $userScopes->pluck('id')->toArray();

            if (!empty($scopedUnitIds)) {
                // Get all children units recursively
                $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                // Include parent units as well
                $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                // If this role has access to the booking order's unit, return true immediately
                if ($allUnitIds->contains($bookingOrder->unit_id)) {
                    return true;
                }
            }
        }

        // No role has access
        return false;
    }

    /**
     * Check if user can approve invoice at current status.
     */
    public function canUserApproveOrReject(Invoice $invoice, User $user): bool
    {
        $currentStatus = $invoice->approval_status ?? InvoiceApprovalStatusEnum::DRAFT;
        $bookingOrder = $invoice->settlement->bookingOrder;
        $recipientStatusCode = $bookingOrder->recipientStatus->status_code ?? null;

        // Special case: If booking order has recipientStatusCode = '011',
        // kasir_unit can directly approve/reject from DRAFT status
        if ($recipientStatusCode === '011') {
            // Only kasir_unit can approve/reject invoices with status_code '011'
            if (!$user->hasRole('operator_unit')) {
                return false;
            }

            // Can approve/reject if invoice is in DRAFT status
            if ($currentStatus !== InvoiceApprovalStatusEnum::DRAFT) {
                return false;
            }

            // Check unit_id scope for kasir_unit
            if ($bookingOrder && $bookingOrder->unit_id) {
                $userScopes = $user->getRoleScopeObjects('operator_unit', Unit::class);
                $scopedUnitIds = $userScopes->pluck('id')->toArray();

                if (!empty($scopedUnitIds)) {
                    // Get all children units recursively
                    $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                    // Include parent units as well
                    $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                    return $allUnitIds->contains($bookingOrder->unit_id);
                }
            }

            return false;
        }

        // Normal flow: Invoice must be in pending status
        if (!$currentStatus->isPending()) {
            return false;
        }

        // Get the role name that can approve at this status
        $requiredRole = $currentStatus->getRoleName();
        if (!$requiredRole) {
            return false;
        }

        // Check if user has the required role
        if (!$user->hasRole($requiredRole)) {
            return false;
        }

        // For kasir_unit, verif_pajak, wr2, kepala_upkab: check scope
        if (in_array($requiredRole, ['kasir_unit', 'kepala_upkab'])) {
            if (!$bookingOrder || !$bookingOrder->unit_id) {
                return false;
            }

            $userScopes = $user->getRoleScopeObjects($requiredRole, Unit::class);
            $scopedUnitIds = $userScopes->pluck('id')->toArray();

            if (empty($scopedUnitIds)) {
                return false;
            }

            // Get all children units
            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);
            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

            return $allUnitIds->contains($bookingOrder->unit_id);
        }

        // For other roles: no scope check needed
        return true;
    }

    /**
     * Get user approval role for current invoice status.
     */
    protected function getUserApprovalRole(Invoice $invoice, User $user): ?string
    {
        $currentStatus = $invoice->approval_status ?? InvoiceApprovalStatusEnum::DRAFT;
        $recipientStatusCode = $invoice->settlement->bookingOrder->recipientStatus->status_code;

        // Special case: If booking order has recipientStatusCode = '011',
        // operator_unit can directly approve/reject from DRAFT status
        if ($recipientStatusCode === '011') {
            return 'operator_unit';
        }

        // Normal flow: Find role that can approve current status
        $userRoles = $user->getRoleNames();

        foreach ($userRoles as $role) {
            if ($currentStatus->canBeApprovedBy($role)) {
                // For operator_unit, kasir_unit, ka_upkab check unit_id scope
                if (in_array($role, ['operator_unit', 'kasir_unit', 'ka_upkab'])) {
                    $bookingOrder = $invoice->settlement->bookingOrder;
                    if ($bookingOrder && $bookingOrder->unit_id) {
                        $userScopes = $user->getRoleScopeObjects($role, Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            if ($allUnitIds->contains($bookingOrder->unit_id)) {
                                return $role;
                            }
                        }
                    }
                } else {
                    // For other roles, just return the role
                    return $role;
                }
            }
        }

        return null;
    }

    /**
     * Create approval log.
     */
    protected function createApprovalLog(
        Invoice $invoice,
        InvoiceApprovalStatusEnum $status,
        string $action,
        User $user,
        ?string $roleName,
        ?string $notes = null
    ): void {
        $bookingOrder = $invoice->settlement->bookingOrder;

        InvoiceApprovalLog::create([
            'invoice_id' => $invoice->id,
            'approval_status' => $status->value,
            'action' => $action,
            'notes' => $notes,
            'action_by' => $user->id,
            'role_name' => $roleName,
            'scope_type' => Unit::class,
            'scope_id' => $bookingOrder->unit_id,
        ]);
    }
}

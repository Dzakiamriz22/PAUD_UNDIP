<?php

namespace App\Services;

use App\Enums\BookingOrderApprovalStatusEnum;
use App\Models\BookingOrder;
use App\Models\BookingOrderApprovalLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingOrderApprovalService
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
     * Submit booking order for approval by operator_unit.
     * If recipient_status.status_code = '011', directly APPROVED without approval process.
     */
    public function submit(BookingOrder $bookingOrder, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($bookingOrder, $user, $notes) {
            // Normal workflow: submit to PENDING_SPV_UNIT
            $bookingOrder->approval_status = BookingOrderApprovalStatusEnum::PENDING_SPV_UNIT;
            $bookingOrder->submitted_by = $user->id;
            $bookingOrder->submitted_at = now();
            $bookingOrder->is_fully_approved = false;

            $bookingOrder->save();

            // Create approval log
            $this->createApprovalLog($bookingOrder, $bookingOrder->approval_status, 'submit', $user, 'operator_unit', $notes);
        });
    }

    /**
     * Approve booking order by current approver.
     */
    public function approve(BookingOrder $bookingOrder, User $user, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            $currentStatus = $bookingOrder->approval_status ?? BookingOrderApprovalStatusEnum::DRAFT;
            $recipientStatusCode = $bookingOrder->recipientStatus?->status_code ?? null;

            // Special case: If booking order has recipientStatusCode = '011',
            // operator_unit can directly approve from DRAFT status (no approval process needed)
            if ($recipientStatusCode === '011') {
                // Directly approve (no approval process needed)
                $bookingOrder->approval_status = BookingOrderApprovalStatusEnum::APPROVED;
                $bookingOrder->is_fully_approved = true;
                $bookingOrder->fully_approved_by = $user->id;
                $bookingOrder->fully_approved_at = now();

                $bookingOrder->order_number = BookingOrder::generateOrderNumber($bookingOrder);

                $bookingOrder->save();

                // Create approval log
                $this->createApprovalLog($bookingOrder, BookingOrderApprovalStatusEnum::APPROVED, 'approve', $user, 'operator_unit', $notes);

                DB::commit();
                return;
            }

            // Get user role for logging
            $userRole = $this->getUserApprovalRole($bookingOrder, $user);

            // Get next status based on recipient status code
            $nextStatus = $currentStatus->getNextStatus();

            if (!$nextStatus) {
                throw new \Exception('No next status available');
            }

            // Update booking order status
            $bookingOrder->approval_status = $nextStatus;

            // Determine if fully approved based on recipient status code
            if ($nextStatus === BookingOrderApprovalStatusEnum::APPROVED) {

                $bookingOrder->order_number = BookingOrder::generateOrderNumber($bookingOrder);

                $bookingOrder->is_fully_approved = true;
                $bookingOrder->fully_approved_by = $user->id;
                $bookingOrder->fully_approved_at = now();
            } else {
                $bookingOrder->is_fully_approved = false;
            }

            $bookingOrder->save();

            // Create approval log
            $this->createApprovalLog($bookingOrder, $nextStatus, 'approve', $user, $userRole, $notes);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject booking order and reset to draft status.
     */
    public function reject(BookingOrder $bookingOrder, User $user, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            // Get user role for logging
            $userRole = $this->getUserApprovalRole($bookingOrder, $user);

            // Reset to draft status (booking order needs to be resubmitted)
            $bookingOrder->approval_status = BookingOrderApprovalStatusEnum::DRAFT;
            $bookingOrder->is_fully_approved = false;
            $bookingOrder->fully_approved_by = null;
            $bookingOrder->fully_approved_at = null;

            $bookingOrder->save();

            // Create approval log
            $this->createApprovalLog(
                $bookingOrder,
                BookingOrderApprovalStatusEnum::REJECTED,
                'reject',
                $user,
                $userRole,
                $notes ?? 'Booking order rejected and reset to draft. Please resubmit after making corrections.'
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancel(BookingOrder $bookingOrder, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($bookingOrder, $user, $notes) {
            $bookingOrder->approval_status = BookingOrderApprovalStatusEnum::CANCELED;
            $bookingOrder->save();

            $this->createApprovalLog($bookingOrder, BookingOrderApprovalStatusEnum::CANCELED, 'cancel', $user, 'operator_unit', $notes ?? 'Booking order canceled.');
        });
    }

    /**
     * Create approval log entry.
     */
    protected function createApprovalLog(
        BookingOrder $bookingOrder,
        BookingOrderApprovalStatusEnum $status,
        string $action,
        User $user,
        string $roleName,
        ?string $notes = null
    ): void {
        BookingOrderApprovalLog::create([
            'booking_order_id' => $bookingOrder->id,
            'approval_status' => $status->value,
            'action' => $action,
            'notes' => $notes,
            'action_by' => $user->id,
            'role_name' => $roleName,
            'scope_type' => \App\Models\Unit::class,
            'scope_id' => $bookingOrder->unit_id,
        ]);
    }

    /**
     * Get the role name that can approve this booking order for the given user.
     */
    protected function getUserApprovalRole(BookingOrder $bookingOrder, User $user): ?string
    {
        $currentStatus = $bookingOrder->approval_status ?? BookingOrderApprovalStatusEnum::DRAFT;
        $recipientStatusCode = $bookingOrder->recipientStatus?->status_code ?? null;

        // Special case: If booking order has recipientStatusCode = '011',
        // operator_unit can directly approve/reject from DRAFT status
        if ($recipientStatusCode === '011') {
            return 'operator_unit';
        }

        // Normal flow: Find role that can approve current status
        $userRoles = $user->getRoleNames();

        foreach ($userRoles as $role) {
            if ($currentStatus->canBeApprovedBy($role)) {
                // For operator_unit, spv_unit, and kasir_unit, check unit_id scope
                if (in_array($role, ['operator_unit', 'spv_unit', 'kasir_unit'])) {
                    if ($bookingOrder->unit_id) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
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
     * Check if user can approve/reject the booking order.
     */
    public function canUserApproveOrReject(BookingOrder $bookingOrder, User $user): bool
    {
        $currentStatus = $bookingOrder->approval_status ?? BookingOrderApprovalStatusEnum::DRAFT;
        $recipientStatusCode = $bookingOrder->recipientStatus?->status_code ?? null;

        // Special case: If booking order has recipientStatusCode = '011',
        // operator_unit can directly approve/reject from DRAFT status
        if ($recipientStatusCode === '011') {
            // Only operator_unit can approve/reject booking orders with status_code '011'
            if (!$user->hasRole('operator_unit')) {
                return false;
            }

            // Can approve/reject if booking order is in DRAFT status
            if ($currentStatus !== BookingOrderApprovalStatusEnum::DRAFT) {
                return false;
            }

            // Check unit_id scope for operator_unit
            if ($bookingOrder->unit_id) {
                $userScopes = $user->getRoleScopeObjects('operator_unit', \App\Models\Unit::class);
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

        // Normal flow: Booking order must be in pending status
        if (!$currentStatus || !$currentStatus->isPending()) {
            return false;
        }

        $userRoles = $user->getRoleNames();

        foreach ($userRoles as $role) {
            if ($currentStatus->canBeApprovedBy($role)) {
                // For operator_unit, spv_unit, and kasir_unit, check unit_id scope
                if (in_array($role, ['operator_unit', 'spv_unit', 'kasir_unit'])) {
                    if ($bookingOrder->unit_id) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            if ($allUnitIds->contains($bookingOrder->unit_id)) {
                                return true;
                            }
                        }
                    }
                } else {
                    // For other roles (like customer), just check if role matches
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can submit the booking order for approval.
     */
    public function canUserSubmit(BookingOrder $bookingOrder, User $user): bool
    {
        // Check if booking order has recipient status code
        $recipientStatusCode = $bookingOrder->recipientStatus?->status_code ?? null;
        if (!$recipientStatusCode) {
            return false;
        }

        // If booking order has recipientStatusCode = '011', it cannot be submitted
        // because it should be directly approved or rejected (no approval process needed)
        if ($recipientStatusCode === '011') {
            return false;
        }

        // Check if booking order is in draft or rejected status
        $approvalStatus = $bookingOrder->approval_status ?? BookingOrderApprovalStatusEnum::DRAFT;
        if (!in_array($approvalStatus, [
            BookingOrderApprovalStatusEnum::DRAFT,
            BookingOrderApprovalStatusEnum::REJECTED
        ])) {
            return false;
        }

        if ($user->hasRole('operator_unit')) {
            // Operator_unit can submit booking orders within their unit scope
            $userScopes = $user->getRoleScopeObjects('operator_unit', \App\Models\Unit::class);
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

    /**
     * Check if invoice can be created for this booking order.
     */
    public function canCreateInvoice(BookingOrder $bookingOrder): bool
    {
        return $bookingOrder->is_fully_approved && ($bookingOrder->approval_status === BookingOrderApprovalStatusEnum::APPROVED);
    }
}

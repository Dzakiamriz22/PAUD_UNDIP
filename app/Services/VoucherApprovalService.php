<?php

namespace App\Services;

use App\Enums\VoucherApprovalStatusEnum;
use App\Models\Unit;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherApprovalLog;
use Illuminate\Support\Facades\DB;

class VoucherApprovalService
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
     * Submit voucher for approval by operator_unit.
     */
    public function submit(Voucher $voucher, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($voucher, $user, $notes) {
            $voucher->approval_status = VoucherApprovalStatusEnum::PENDING_SPV_UNIT;
            $voucher->submitted_by = $user->id;
            $voucher->submitted_at = now();
            $voucher->is_fully_approved = false;
            $voucher->is_active = false; // Voucher tidak aktif jika belum fully approved
            $voucher->save();

            // Create approval log
            VoucherApprovalLog::create([
                'voucher_id' => $voucher->id,
                'approval_status' => $voucher->approval_status->value,
                'action' => 'submit',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => 'operator_unit',
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $voucher->unit_id,
            ]);
        });
    }

    /**
     * Approve voucher by current approver.
     */
    public function approve(Voucher $voucher, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($voucher, $user, $notes) {
            $currentStatus = $voucher->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Voucher is not in pending status');
            }

            // Check if user can approve
            $userRoles = $user->getRoleNames();
            $canApprove = false;
            $userRole = null;

            // Roles that require unit_id scope filtering
            $scopedRoles = ['operator_unit', 'spv_unit', 'kasir_unit', 'manajer_upkab', 'ka_upkab'];

            foreach ($userRoles as $role) {
                if ($currentStatus->canBeApprovedBy($role)) {
                    // If role is in scoped roles, check unit_id scope (including children)
                    if (in_array($role, $scopedRoles)) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            $hasScope = $allUnitIds->contains($voucher->unit_id);

                            if ($hasScope) {
                                $canApprove = true;
                                $userRole = $role;
                                break;
                            }
                        }
                    } else {
                        // For other roles, no unit_id filtering needed
                        $canApprove = true;
                        $userRole = $role;
                        break;
                    }
                }
            }

            if (!$canApprove) {
                throw new \Exception('User does not have permission to approve this voucher');
            }

            // Get next status
            $nextStatus = $currentStatus->getNextStatus();

            if (!$nextStatus) {
                throw new \Exception('No next status available');
            }

            // Update voucher status
            $voucher->approval_status = $nextStatus;

            // If approved by ka_upkab, mark as fully approved
            if ($nextStatus === VoucherApprovalStatusEnum::APPROVED) {
                $voucher->is_fully_approved = true;
                $voucher->is_active = true;
                // is_active bisa di-set manual oleh user setelah fully approved
            } else {
                // Selama belum fully approved, voucher tidak aktif
                $voucher->is_fully_approved = false;
                $voucher->is_active = false;
            }

            $voucher->save();

            // Create approval log
            $logData = [
                'voucher_id' => $voucher->id,
                'approval_status' => $nextStatus->value,
                'action' => 'approve',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $voucher->unit_id,
            ];

            VoucherApprovalLog::create($logData);
        });
    }

    /**
     * Reject voucher and reset to draft status.
     */
    public function reject(Voucher $voucher, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($voucher, $user, $notes) {
            $currentStatus = $voucher->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Voucher is not in pending status');
            }

            // Check if user can reject (same logic as approve)
            $userRoles = $user->getRoleNames();
            $canReject = false;
            $userRole = null;

            // Roles that require unit_id scope filtering
            $scopedRoles = ['operator_unit', 'spv_unit', 'kasir_unit', 'manajer_upkab', 'ka_upkab'];

            foreach ($userRoles as $role) {
                if ($currentStatus->canBeApprovedBy($role)) {
                    // If role is in scoped roles, check unit_id scope (including children)
                    if (in_array($role, $scopedRoles)) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            $hasScope = $allUnitIds->contains($voucher->unit_id);

                            if ($hasScope) {
                                $canReject = true;
                                $userRole = $role;
                                break;
                            }
                        }
                    } else {
                        // For other roles, no unit_id filtering needed
                        $canReject = true;
                        $userRole = $role;
                        break;
                    }
                }
            }

            if (!$canReject) {
                throw new \Exception('User does not have permission to reject this voucher');
            }

            // Reset to draft status (voucher needs to be resubmitted)
            $voucher->approval_status = VoucherApprovalStatusEnum::DRAFT;
            $voucher->is_fully_approved = false;
            $voucher->is_active = false; // Voucher tidak aktif jika belum fully approved
            $voucher->save();

            // Create approval log
            VoucherApprovalLog::create([
                'voucher_id' => $voucher->id,
                'approval_status' => VoucherApprovalStatusEnum::DRAFT->value,
                'action' => 'reject',
                'notes' => $notes ?? 'Voucher rejected and reset to draft. Please resubmit after making corrections.',
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $voucher->unit_id,
            ]);
        });
    }

    /**
     * Check if user can approve/reject the voucher.
     */
    public function canUserApprove(Voucher $voucher, User $user): bool
    {
        if (!$voucher->approval_status || !$voucher->approval_status->isPending()) {
            return false;
        }

        $userRoles = $user->getRoleNames();

        // Roles that require unit_id scope filtering
        $scopedRoles = ['operator_unit', 'spv_unit', 'kasir_unit', 'manajer_upkab', 'ka_upkab'];

        foreach ($userRoles as $role) {
            if ($voucher->approval_status->canBeApprovedBy($role)) {
                // If role is in scoped roles, check unit_id scope (including children)
                if (in_array($role, $scopedRoles)) {
                    $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                    $scopedUnitIds = $userScopes->pluck('id')->toArray();

                    if (!empty($scopedUnitIds)) {
                        // Get all children units recursively
                        $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                        // Include parent units as well
                        $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                        if ($allUnitIds->contains($voucher->unit_id)) {
                            return true;
                        }
                    }
                } else {
                    // For other roles, no unit_id filtering needed
                    return true;
                }
            }
        }

        return false;
    }
}


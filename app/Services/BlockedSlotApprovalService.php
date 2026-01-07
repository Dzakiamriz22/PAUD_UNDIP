<?php

namespace App\Services;

use App\Enums\BlockedSlotApprovalStatusEnum;
use App\Models\BlockedSlot;
use App\Models\BlockedSlotApprovalLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BlockedSlotApprovalService
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
     * Submit blocked slot for approval by operator_unit.
     */
    public function submit(BlockedSlot $blockedSlot, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($blockedSlot, $user, $notes) {
            // Get unit_id from product
            $unitId = $blockedSlot->product->unit_id ?? null;

            $blockedSlot->approval_status = BlockedSlotApprovalStatusEnum::PENDING_SPV_UNIT;
            $blockedSlot->submitted_by = $user->id;
            $blockedSlot->submitted_at = now();
            $blockedSlot->is_fully_approved = false;
            $blockedSlot->unit_id = $unitId;
            $blockedSlot->save();

            // Create approval log
            BlockedSlotApprovalLog::create([
                'blocked_slot_id' => $blockedSlot->id,
                'approval_status' => $blockedSlot->approval_status->value,
                'action' => 'submit',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => 'operator_unit',
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $unitId,
            ]);
        });
    }

    /**
     * Approve blocked slot by current approver.
     */
    public function approve(BlockedSlot $blockedSlot, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($blockedSlot, $user, $notes) {
            $currentStatus = $blockedSlot->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Blocked slot is not in pending status');
            }

            // Check if user can approve
            $userRoles = $user->getRoleNames();
            $canApprove = false;
            $userRole = null;

            // Roles that require unit_id scope filtering
            $scopedRoles = ['operator_unit', 'spv_unit', 'manajer_upkab', 'ka_upkab'];

            foreach ($userRoles as $role) {
                if ($currentStatus->canBeApprovedBy($role)) {
                    // Check unit_id scope (including children)
                    if ($blockedSlot->unit_id) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            $hasScope = $allUnitIds->contains($blockedSlot->unit_id);

                            if ($hasScope) {
                                $canApprove = true;
                                $userRole = $role;
                                break;
                            }
                        }
                    }
                }
            }

            if (!$canApprove) {
                throw new \Exception('User does not have permission to approve this blocked slot');
            }

            // Get next status
            $nextStatus = $currentStatus->getNextStatus();

            if (!$nextStatus) {
                throw new \Exception('No next status available');
            }

            // Update blocked slot status
            $blockedSlot->approval_status = $nextStatus;

            // If approved by ka_upkab, mark as fully approved
            if ($nextStatus === BlockedSlotApprovalStatusEnum::APPROVED) {
                $blockedSlot->is_fully_approved = true;
            } else {
                $blockedSlot->is_fully_approved = false;
            }

            $blockedSlot->save();

            // Create approval log
            $logData = [
                'blocked_slot_id' => $blockedSlot->id,
                'approval_status' => $nextStatus->value,
                'action' => 'approve',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $blockedSlot->unit_id,
            ];

            BlockedSlotApprovalLog::create($logData);
        });
    }

    /**
     * Reject blocked slot and reset to draft status.
     */
    public function reject(BlockedSlot $blockedSlot, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($blockedSlot, $user, $notes) {
            $currentStatus = $blockedSlot->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Blocked slot is not in pending status');
            }

            // Check if user can reject (same logic as approve)
            $userRoles = $user->getRoleNames();
            $canReject = false;
            $userRole = null;

            // Roles that require unit_id scope filtering
            $scopedRoles = ['operator_unit', 'spv_unit', 'manajer_upkab', 'ka_upkab'];

            foreach ($userRoles as $role) {
                if ($currentStatus->canBeApprovedBy($role)) {
                    // Check unit_id scope (including children)
                    if ($blockedSlot->unit_id) {
                        $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                        $scopedUnitIds = $userScopes->pluck('id')->toArray();

                        if (!empty($scopedUnitIds)) {
                            // Get all children units recursively
                            $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                            // Include parent units as well
                            $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                            $hasScope = $allUnitIds->contains($blockedSlot->unit_id);

                            if ($hasScope) {
                                $canReject = true;
                                $userRole = $role;
                                break;
                            }
                        }
                    }
                }
            }

            if (!$canReject) {
                throw new \Exception('User does not have permission to reject this blocked slot');
            }

            // Reset to draft status (blocked slot needs to be resubmitted)
            $blockedSlot->approval_status = BlockedSlotApprovalStatusEnum::DRAFT;
            $blockedSlot->is_fully_approved = false;
            $blockedSlot->save();

            // Create approval log
            BlockedSlotApprovalLog::create([
                'blocked_slot_id' => $blockedSlot->id,
                'approval_status' => BlockedSlotApprovalStatusEnum::DRAFT->value,
                'action' => 'reject',
                'notes' => $notes ?? 'Blocked slot rejected and reset to draft. Please resubmit after making corrections.',
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $blockedSlot->unit_id,
            ]);
        });
    }

    /**
     * Check if user can approve/reject the blocked slot.
     */
    public function canUserApprove(BlockedSlot $blockedSlot, User $user): bool
    {
        if (!$blockedSlot->approval_status || !$blockedSlot->approval_status->isPending()) {
            return false;
        }

        $userRoles = $user->getRoleNames();

        // Roles that require unit_id scope filtering
        $scopedRoles = ['operator_unit', 'spv_unit', 'manajer_upkab', 'ka_upkab'];

        foreach ($userRoles as $role) {
            if ($blockedSlot->approval_status->canBeApprovedBy($role)) {
                // Check unit_id scope (including children)
                if ($blockedSlot->unit_id) {
                    $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                    $scopedUnitIds = $userScopes->pluck('id')->toArray();

                    if (!empty($scopedUnitIds)) {
                        // Get all children units recursively
                        $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                        // Include parent units as well
                        $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                        if ($allUnitIds->contains($blockedSlot->unit_id)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}


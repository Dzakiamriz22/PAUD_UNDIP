<?php

namespace App\Services;

use App\Enums\ProductApprovalStatusEnum;
use App\Models\Product;
use App\Models\ProductApprovalLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductApprovalService
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
     * Submit product for approval by operator_unit.
     */
    public function submit(Product $product, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($product, $user, $notes) {
            $product->approval_status = ProductApprovalStatusEnum::PENDING_SPV_UNIT;
            $product->submitted_by = $user->id;
            $product->submitted_at = now();
            $product->is_fully_approved = false;
            $product->is_active = false; // Product tidak aktif jika belum fully approved
            $product->save();

            // Create approval log
            ProductApprovalLog::create([
                'product_id' => $product->id,
                'approval_status' => $product->approval_status->value,
                'action' => 'submit',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => 'operator_unit',
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $product->unit_id,
            ]);
        });
    }

    /**
     * Approve product by current approver.
     */
    public function approve(Product $product, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($product, $user, $notes) {
            $currentStatus = $product->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Product is not in pending status');
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

                            $hasScope = $allUnitIds->contains($product->unit_id);

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
                throw new \Exception('User does not have permission to approve this product');
            }

            // Get next status
            $nextStatus = $currentStatus->getNextStatus();

            if (!$nextStatus) {
                throw new \Exception('No next status available');
            }

            // Update product status
            $product->approval_status = $nextStatus;

            // If approved by ka_upkab, mark as fully approved
            if ($nextStatus === ProductApprovalStatusEnum::APPROVED) {
                $product->is_fully_approved = true;
                $product->is_active = true;
                // is_active bisa di-set manual oleh user setelah fully approved
            } else {
                // Selama belum fully approved, product tidak aktif
                $product->is_fully_approved = false;
                $product->is_active = false;
            }

            $product->save();

            // Create approval log
            $logData = [
                'product_id' => $product->id,
                'approval_status' => $nextStatus->value,
                'action' => 'approve',
                'notes' => $notes,
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $product->unit_id,
            ];

            ProductApprovalLog::create($logData);
        });
    }

    /**
     * Reject product and reset to draft status.
     */
    public function reject(Product $product, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($product, $user, $notes) {
            $currentStatus = $product->approval_status;

            if (!$currentStatus || !$currentStatus->isPending()) {
                throw new \Exception('Product is not in pending status');
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

                            $hasScope = $allUnitIds->contains($product->unit_id);

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
                throw new \Exception('User does not have permission to reject this product');
            }

            // Reset to draft status (product needs to be resubmitted)
            $product->approval_status = ProductApprovalStatusEnum::DRAFT;
            $product->is_fully_approved = false;
            $product->is_active = false; // Product tidak aktif jika belum fully approved
            $product->save();

            // Create approval log
            ProductApprovalLog::create([
                'product_id' => $product->id,
                'approval_status' => ProductApprovalStatusEnum::DRAFT->value,
                'action' => 'reject',
                'notes' => $notes ?? 'Product rejected and reset to draft. Please resubmit after making corrections.',
                'action_by' => $user->id,
                'role_name' => $userRole,
                'scope_type' => \App\Models\Unit::class,
                'scope_id' => $product->unit_id,
            ]);
        });
    }

    /**
     * Check if user can approve/reject the product.
     */
    public function canUserApprove(Product $product, User $user): bool
    {
        if (!$product->approval_status || !$product->approval_status->isPending()) {
            return false;
        }

        $userRoles = $user->getRoleNames();

        // Roles that require unit_id scope filtering
        $scopedRoles = ['operator_unit', 'spv_unit', 'kasir_unit', 'manajer_upkab', 'ka_upkab'];

        foreach ($userRoles as $role) {
            if ($product->approval_status->canBeApprovedBy($role)) {
                // If role is in scoped roles, check unit_id scope (including children)
                if (in_array($role, $scopedRoles)) {
                    $userScopes = $user->getRoleScopeObjects($role, \App\Models\Unit::class);
                    $scopedUnitIds = $userScopes->pluck('id')->toArray();

                    if (!empty($scopedUnitIds)) {
                        // Get all children units recursively
                        $allUnitIds = $this->getAllChildrenUnitIds($scopedUnitIds);

                        // Include parent units as well
                        $allUnitIds = $allUnitIds->merge($scopedUnitIds)->unique()->values();

                        if ($allUnitIds->contains($product->unit_id)) {
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

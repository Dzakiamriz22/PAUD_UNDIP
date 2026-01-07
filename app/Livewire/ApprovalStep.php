<?php

namespace App\Livewire;

use App\Enums\BlockedSlotApprovalStatusEnum;
use App\Enums\BookingOrderApprovalStatusEnum;
use App\Enums\InvoiceApprovalStatusEnum;
use App\Enums\ProductApprovalStatusEnum;
use App\Enums\VoucherApprovalStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Attributes\On;

class ApprovalStep extends Component
{
    public ?Model $model = null;
    public ?string $modelClass = null;
    public array $approval_step_config = [];
    public int $current_step = -1;
    public bool $is_finished = false;
    public array $steps = [];

    public function mount(?Model $model = null, ?string $modelClass = null, ?int $modelId = null, array $approval_step_config = [])
    {
        $this->approval_step_config = $approval_step_config;

        // Load model if provided
        if ($model) {
            $this->model = $model;
            $this->modelClass = get_class($model);
        } elseif ($modelClass && $modelId) {
            $this->modelClass = $modelClass;
            $this->model = $modelClass::find($modelId);
        }

        $this->initializeSteps();
    }

    protected function initializeSteps()
    {
        if (!$this->model) {
            $this->current_step = -1;
            $this->is_finished = false;
            $this->steps = [];
            return;
        }

        // Get approval steps based on model type
        $this->steps = $this->getApprovalSteps();

        // Get approval status from model
        $approvalStatus = $this->getApprovalStatus();

        // Find current step index
        $this->current_step = $this->getCurrentStepIndex($approvalStatus);

        // Check if finished
        $this->is_finished = $this->isApproved($approvalStatus);
    }

    /**
     * Get approval steps based on model type.
     * Override this method or use approval_step_config to customize steps.
     */
    protected function getApprovalSteps(): array
    {
        // Check if custom steps are provided in config
        if (!empty($this->approval_step_config['steps'])) {
            return $this->approval_step_config['steps'];
        }

        // Default steps for ProductApprovalStatusEnum
        // This can be extended for other approval enums
        if ($this->usesProductApprovalStatus()) {
            return [
                [
                    'approval_name' => 'Diusulkan Operator',
                    'status' => ProductApprovalStatusEnum::DRAFT->value,
                ],
                [
                    'approval_name' => 'SPV Unit',
                    'status' => ProductApprovalStatusEnum::PENDING_SPV_UNIT->value,
                ],
                [
                    'approval_name' => 'Manajer UPKAB',
                    'status' => ProductApprovalStatusEnum::PENDING_MANAJER_UPKAB->value,
                ],
                [
                    'approval_name' => 'QC Produk',
                    'status' => ProductApprovalStatusEnum::PENDING_QC_PRODUK->value,
                ],
                [
                    'approval_name' => 'Verifikator Pajak',
                    'status' => ProductApprovalStatusEnum::PENDING_VER_PAJAK->value,
                ],
                [
                    'approval_name' => 'KA UPKAB',
                    'status' => ProductApprovalStatusEnum::PENDING_KA_UPKAB->value,
                ],
            ];
        }

        // Steps for BlockedSlotApprovalStatusEnum
        if ($this->usesBlockedSlotApprovalStatus()) {
            return [
                [
                    'approval_name' => 'Diusulkan Operator',
                    'status' => BlockedSlotApprovalStatusEnum::DRAFT->value,
                ],
                [
                    'approval_name' => 'SPV Unit',
                    'status' => BlockedSlotApprovalStatusEnum::PENDING_SPV_UNIT->value,
                ],
                [
                    'approval_name' => 'Manajer UPKAB',
                    'status' => BlockedSlotApprovalStatusEnum::PENDING_MANAJER_UPKAB->value,
                ],
                [
                    'approval_name' => 'KA UPKAB',
                    'status' => BlockedSlotApprovalStatusEnum::PENDING_KA_UPKAB->value,
                ],
            ];
        }

        // Steps for BookingOrderApprovalStatusEnum
        if ($this->usesBookingOrderApprovalStatus()) {
            // // Get recipient status code to determine workflow
            // $recipientStatusCode = $this->model->recipientStatus?->status_code ?? null;

            // // If status_code = '011', no approval needed: DRAFT -> directly APPROVED
            // if ($recipientStatusCode === '011') {
            //     return [
            //         [
            //             'approval_name' => 'Diusulkan Operator Unit',
            //             'status' => BookingOrderApprovalStatusEnum::DRAFT->value,
            //         ],
            //     ];
            // }

            // Otherwise, full workflow: DRAFT -> operator_unit submit -> SPV_UNIT -> kasir_unit -> APPROVED
            return [
                [
                    'approval_name' => 'Diusulkan Operator Unit',
                    'status' => BookingOrderApprovalStatusEnum::DRAFT->value,
                ],
                [
                    'approval_name' => 'SPV Unit',
                    'status' => BookingOrderApprovalStatusEnum::PENDING_SPV_UNIT->value,
                ],
                [
                    'approval_name' => 'Kasir Unit',
                    'status' => BookingOrderApprovalStatusEnum::PENDING_KASIR_UNIT->value,
                ],
            ];
        }

        // Steps for VoucherApprovalStatusEnum
        if ($this->usesVoucherApprovalStatus()) {
            return [
                [
                    'approval_name' => 'Diusulkan Operator',
                    'status' => VoucherApprovalStatusEnum::DRAFT->value,
                ],
                [
                    'approval_name' => 'SPV Unit',
                    'status' => VoucherApprovalStatusEnum::PENDING_SPV_UNIT->value,
                ],
                [
                    'approval_name' => 'Manajer UPKAB',
                    'status' => VoucherApprovalStatusEnum::PENDING_MANAJER_UPKAB->value,
                ],
                [
                    'approval_name' => 'KA UPKAB',
                    'status' => VoucherApprovalStatusEnum::PENDING_KA_UPKAB->value,
                ],
            ];
        }

        // Steps for InvoiceApprovalStatusEnum
        if ($this->usesInvoiceApprovalStatus()) {
            // Get payment method to determine workflow
            $bookingOrder = $this->model->settlement->bookingOrder;
            $paymentMethod = $bookingOrder->paymentMethod;
            $paymentCode = $paymentMethod->payment_code ?? null;

            $steps = [
                [
                    'approval_name' => 'Draft',
                    'status' => InvoiceApprovalStatusEnum::DRAFT->value,
                ],
            ];

            // If payment_code is NOT "01", add WR2 step
            // If payment_code is "01", skip WR2 step (Virtual Account)
            if ($paymentCode !== '01') {
                $steps[] = [
                    'approval_name' => 'WR 2',
                    'status' => InvoiceApprovalStatusEnum::PENDING_WR2->value,
                ];
            }

            // Add remaining steps
            $steps[] = [
                'approval_name' => 'Verifikasi Pajak',
                'status' => InvoiceApprovalStatusEnum::PENDING_VERIF_PAJAK->value,
            ];
            $steps[] = [
                'approval_name' => 'Kepala UPKAB',
                'status' => InvoiceApprovalStatusEnum::PENDING_KEPALA_UPKAB->value,
            ];

            return $steps;
        }

        // Return empty steps if no matching approval enum
        return [];
    }

    /**
     * Get approval status from model.
     */
    protected function getApprovalStatus(): ProductApprovalStatusEnum|BlockedSlotApprovalStatusEnum|BookingOrderApprovalStatusEnum|VoucherApprovalStatusEnum|InvoiceApprovalStatusEnum|null
    {
        if (!$this->model) {
            return null;
        }

        // Try to get approval_status attribute
        $approvalStatus = $this->model->approval_status ?? null;

        // If no approval status, check if it has been submitted
        if (!$approvalStatus) {
            // If submitted_at exists, it means it was submitted but status might be null
            // Otherwise, it's still in draft
            if (isset($this->model->submitted_at) && $this->model->submitted_at) {
                // Assume it's at the first pending step
                if ($this->usesProductApprovalStatus()) {
                    return ProductApprovalStatusEnum::PENDING_SPV_UNIT;
                }
                if ($this->usesBlockedSlotApprovalStatus()) {
                    return BlockedSlotApprovalStatusEnum::PENDING_SPV_UNIT;
                }
                if ($this->usesBookingOrderApprovalStatus()) {
                    return BookingOrderApprovalStatusEnum::PENDING_SPV_UNIT;
                }
                if ($this->usesVoucherApprovalStatus()) {
                    return VoucherApprovalStatusEnum::PENDING_SPV_UNIT;
                }
                if ($this->usesInvoiceApprovalStatus()) {
                    // Get payment method to determine first pending step
                    $bookingOrder = $this->model->settlement->bookingOrder;
                    $paymentMethod = $bookingOrder->paymentMethod;
                    $paymentCode = $paymentMethod->payment_code ?? null;

                    // If payment_code is NOT "01", first step is PENDING_WR2
                    // If payment_code is "01", skip WR2 and go to PENDING_VERIF_PAJAK
                    if ($paymentCode !== '01') {
                        return InvoiceApprovalStatusEnum::PENDING_WR2;
                    }
                    return InvoiceApprovalStatusEnum::PENDING_VERIF_PAJAK;
                }
            } else {
                if ($this->usesProductApprovalStatus()) {
                    return ProductApprovalStatusEnum::DRAFT;
                }
                if ($this->usesBlockedSlotApprovalStatus()) {
                    return BlockedSlotApprovalStatusEnum::DRAFT;
                }
                if ($this->usesBookingOrderApprovalStatus()) {
                    return BookingOrderApprovalStatusEnum::DRAFT;
                }
                if ($this->usesVoucherApprovalStatus()) {
                    return VoucherApprovalStatusEnum::DRAFT;
                }
                if ($this->usesInvoiceApprovalStatus()) {
                    return InvoiceApprovalStatusEnum::DRAFT;
                }
            }
        }

        return $approvalStatus;
    }

    /**
     * Check if model uses ProductApprovalStatusEnum.
     */
    protected function usesProductApprovalStatus(): bool
    {
        if (!$this->model) {
            return false;
        }

        // Check if model has approval_status that is ProductApprovalStatusEnum
        $approvalStatus = $this->model->approval_status;

        return $approvalStatus instanceof ProductApprovalStatusEnum;
    }

    /**
     * Check if model uses BlockedSlotApprovalStatusEnum.
     */
    protected function usesBlockedSlotApprovalStatus(): bool
    {
        if (!$this->model) {
            return false;
        }

        // Check if model has approval_status that is BlockedSlotApprovalStatusEnum
        $approvalStatus = $this->model->approval_status;

        return $approvalStatus instanceof BlockedSlotApprovalStatusEnum;
    }

    /**
     * Check if model uses BookingOrderApprovalStatusEnum.
     */
    protected function usesBookingOrderApprovalStatus(): bool
    {
        if (!$this->model) {
            return false;
        }

        // Check if model has approval_status that is BookingOrderApprovalStatusEnum
        $approvalStatus = $this->model->approval_status;

        return $approvalStatus instanceof BookingOrderApprovalStatusEnum;
    }

    /**
     * Check if model uses VoucherApprovalStatusEnum.
     */
    protected function usesVoucherApprovalStatus(): bool
    {
        if (!$this->model) {
            return false;
        }

        // Check if model has approval_status that is VoucherApprovalStatusEnum
        $approvalStatus = $this->model->approval_status;

        return $approvalStatus instanceof VoucherApprovalStatusEnum;
    }

    /**
     * Check if model uses InvoiceApprovalStatusEnum.
     */
    protected function usesInvoiceApprovalStatus(): bool
    {
        if (!$this->model) {
            return false;
        }

        // Check if model has approval_status that is InvoiceApprovalStatusEnum
        $approvalStatus = $this->model->approval_status;

        return $approvalStatus instanceof InvoiceApprovalStatusEnum;
    }

    /**
     * Get current step index based on approval status.
     */
    protected function getCurrentStepIndex(ProductApprovalStatusEnum|BlockedSlotApprovalStatusEnum|BookingOrderApprovalStatusEnum|VoucherApprovalStatusEnum|InvoiceApprovalStatusEnum|null $status): int
    {
        if (!$status) {
            return -1;
        }

        // Map ProductApprovalStatusEnum to current step index
        if ($status instanceof ProductApprovalStatusEnum) {
            return match ($status) {
                ProductApprovalStatusEnum::DRAFT => 0, // At step 0 (Diusulkan Operator)
                ProductApprovalStatusEnum::PENDING_SPV_UNIT => 1, // At step 1 (SPV Unit)
                ProductApprovalStatusEnum::PENDING_MANAJER_UPKAB => 2, // At step 2 (Manajer UPKAB)
                ProductApprovalStatusEnum::PENDING_QC_PRODUK => 3, // At step 3 (QC Produk)
                ProductApprovalStatusEnum::PENDING_VER_PAJAK => 4, // At step 4 (Verifikator Pajak)
                ProductApprovalStatusEnum::PENDING_KA_UPKAB => 5, // At step 5 (KA UPKAB)
                ProductApprovalStatusEnum::APPROVED => 6, // Finished - all steps completed
                ProductApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                default => -1,
            };
        }

        // Map VoucherApprovalStatusEnum to current step index
        if ($status instanceof VoucherApprovalStatusEnum) {
            return match ($status) {
                VoucherApprovalStatusEnum::DRAFT => 0, // At step 0 (Diusulkan Operator)
                VoucherApprovalStatusEnum::PENDING_SPV_UNIT => 1, // At step 1 (SPV Unit)
                VoucherApprovalStatusEnum::PENDING_MANAJER_UPKAB => 2, // At step 2 (Manajer UPKAB)
                VoucherApprovalStatusEnum::PENDING_KA_UPKAB => 3, // At step 3 (KA UPKAB)
                VoucherApprovalStatusEnum::APPROVED => 4, // Finished - all steps completed
                VoucherApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                default => -1,
            };
        }

        // Map BlockedSlotApprovalStatusEnum to current step index
        if ($status instanceof BlockedSlotApprovalStatusEnum) {
            return match ($status) {
                BlockedSlotApprovalStatusEnum::DRAFT => 0, // At step 0 (Diusulkan Operator)
                BlockedSlotApprovalStatusEnum::PENDING_SPV_UNIT => 1, // At step 1 (SPV Unit)
                BlockedSlotApprovalStatusEnum::PENDING_MANAJER_UPKAB => 2, // At step 2 (Manajer UPKAB)
                BlockedSlotApprovalStatusEnum::PENDING_KA_UPKAB => 3, // At step 3 (KA UPKAB)
                BlockedSlotApprovalStatusEnum::APPROVED => 4, // Finished - all steps completed
                BlockedSlotApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                default => -1,
            };
        }

        // Map BookingOrderApprovalStatusEnum to current step index
        if ($status instanceof BookingOrderApprovalStatusEnum) {
            // Get recipient status code to determine workflow
            // $recipientStatusCode = $this->model->recipientStatus?->status_code ?? null;

            // // If status_code = '011', no approval needed: DRAFT -> directly APPROVED (1 step)
            // if ($recipientStatusCode === '011') {
            //     return match ($status) {
            //         BookingOrderApprovalStatusEnum::DRAFT => 0, // At step 0 (Diusulkan Operator Unit)
            //         BookingOrderApprovalStatusEnum::APPROVED => 1, // Finished - all steps completed
            //         BookingOrderApprovalStatusEnum::REJECTED => -1, // Reset to beginning
            //         default => -1,
            //     };
            // }

            // Otherwise, full workflow: DRAFT -> operator submit -> SPV -> kasir -> APPROVED (3 steps)
            return match ($status) {
                BookingOrderApprovalStatusEnum::DRAFT => 0, // At step 0 (Diusulkan Operator Unit)
                BookingOrderApprovalStatusEnum::PENDING_SPV_UNIT => 1, // At step 1 (Waiting for SPV approve)
                BookingOrderApprovalStatusEnum::PENDING_KASIR_UNIT => 2, // At step 2 (SPV approved - waiting for kasir approve)
                BookingOrderApprovalStatusEnum::APPROVED => 3, // Finished - all steps completed
                BookingOrderApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                default => -1,
            };
        }

        if ($status instanceof InvoiceApprovalStatusEnum) {
            // Get payment method to determine workflow
            $bookingOrder = $this->model->settlement?->bookingOrder;
            $paymentMethod = $bookingOrder->paymentMethod;
            $paymentCode = $paymentMethod->payment_code ?? null;

            // If payment_code is "01", skip WR2 step
            if ($paymentCode === '01') {
                return match ($status) {
                    InvoiceApprovalStatusEnum::DRAFT => 0, // At step 0 (Draft)
                    InvoiceApprovalStatusEnum::PENDING_VERIF_PAJAK => 1, // At step 1 (Pending Verif Pajak) - skip WR2
                    InvoiceApprovalStatusEnum::PENDING_KEPALA_UPKAB => 2, // At step 2 (Pending Kepala UPKAB)
                    InvoiceApprovalStatusEnum::APPROVED => 3, // Finished - all steps completed
                    InvoiceApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                    default => -1,
                };
            }

            // If payment_code is NOT "01", include WR2 step
            return match ($status) {
                InvoiceApprovalStatusEnum::DRAFT => 0, // At step 0 (Draft)
                InvoiceApprovalStatusEnum::PENDING_WR2 => 1, // At step 1 (Pending WR2)
                InvoiceApprovalStatusEnum::PENDING_VERIF_PAJAK => 2, // At step 2 (Pending Verif Pajak)
                InvoiceApprovalStatusEnum::PENDING_KEPALA_UPKAB => 3, // At step 3 (Pending Kepala UPKAB)
                InvoiceApprovalStatusEnum::APPROVED => 4, // Finished - all steps completed
                InvoiceApprovalStatusEnum::REJECTED => -1, // Reset to beginning
                default => -1,
            };
        }

        return -1;
    }

    /**
     * Check if approval status is approved.
     */
    protected function isApproved(ProductApprovalStatusEnum|BlockedSlotApprovalStatusEnum|BookingOrderApprovalStatusEnum|VoucherApprovalStatusEnum|InvoiceApprovalStatusEnum|null $status): bool
    {
        if (!$status) {
            return false;
        }

        if ($status instanceof ProductApprovalStatusEnum) {
            return $status === ProductApprovalStatusEnum::APPROVED;
        }

        if ($status instanceof BlockedSlotApprovalStatusEnum) {
            return $status === BlockedSlotApprovalStatusEnum::APPROVED;
        }

        if ($status instanceof BookingOrderApprovalStatusEnum) {
            return $status === BookingOrderApprovalStatusEnum::APPROVED;
        }

        if ($status instanceof VoucherApprovalStatusEnum) {
            return $status === VoucherApprovalStatusEnum::APPROVED;
        }

        if ($status instanceof InvoiceApprovalStatusEnum) {
            return $status === InvoiceApprovalStatusEnum::APPROVED;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.approval-step');
    }

    #[On('update-approval')]
    public function set_approval_step($modelId, $modelClass = null)
    {
        if ($modelId) {
            // Use provided model class or try to determine from current model
            $class = $modelClass ?? $this->modelClass;

            if (!$class && $this->model) {
                $class = get_class($this->model);
            }

            if ($class && class_exists($class)) {
                // Load the model
                $this->model = $class::find($modelId);
                $this->modelClass = $class;

                if ($this->model) {
                    $this->initializeSteps();
                }
            }
        }
    }

    #[On('update-approval-step')]
    public function update_approval_step()
    {
        $this->mount();
    }
}

<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Invoice;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;

    public function mount(): void
    {
        parent::mount();

        // Jika ada invoice_id dari query parameter URL, set ke form
        $invoiceId = request()->query('invoice_id');
        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice && !$invoice->receipt) {
                $fill = [
                    'invoice_id' => $invoiceId,
                    'amount_paid' => $invoice->total_amount,
                ];

                // Jika invoice memiliki VA, isi metode pembayaran dan nomor referensi
                if (!empty($invoice->va_number)) {
                    $fill['payment_method'] = 'va';
                    $fill['reference_number'] = $invoice->va_number;
                }

                $fill['issued_at'] = now();

                $this->form->fill($fill);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan receipt_number tidak di-set, biarkan booted() yang generate
        unset($data['receipt_number']);

        // Validasi: Pastikan invoice belum punya receipt
        if (isset($data['invoice_id'])) {
            $invoice = Invoice::with('receipt')->find($data['invoice_id']);
            
            if (!$invoice) {
                throw new \Exception('Invoice tidak ditemukan.');
            }

            if ($invoice->receipt) {
                throw new \Exception('Invoice ini sudah memiliki kuitansi. Satu invoice hanya dapat memiliki satu kuitansi.');
            }

            // Update invoice status menjadi paid jika amount_paid >= total_amount
            if ($data['amount_paid'] >= $invoice->total_amount) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => $data['payment_date'] ?? now(),
                ]);
            }
        }

        // Validasi: payment_date tidak boleh lebih besar dari issued_at
        if (!empty($data['payment_date']) && !empty($data['issued_at'])) {
            try {
                $payment = \Carbon\Carbon::parse($data['payment_date']);
                $issued = \Carbon\Carbon::parse($data['issued_at']);
                if ($payment->gt($issued)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'payment_date' => 'Tanggal pembayaran tidak boleh lebih dari tanggal kuitansi diterbitkan.',
                    ]);
                }
            } catch (\Exception $e) {
                // Jika parsing gagal, biarkan validasi default handle atau lempar pesan validasi
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_date' => 'Tanggal pembayaran tidak valid.',
                ]);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Remove the "Create & create another" action — only keep Create and Cancel.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Kuitansi berhasil dibuat')
            ->body('Kuitansi telah berhasil dibuat dan invoice telah diperbarui.');
    }
}


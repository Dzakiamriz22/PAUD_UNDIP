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
                $this->form->fill([
                    'invoice_id' => $invoiceId,
                    'amount_paid' => $invoice->total_amount,
                ]);
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

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Kuitansi berhasil dibuat')
            ->body('Kuitansi telah berhasil dibuat dan invoice telah diperbarui.');
    }
}


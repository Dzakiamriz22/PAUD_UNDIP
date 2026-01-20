<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\Invoice;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update invoice status jika amount_paid berubah
        if (isset($data['invoice_id'])) {
            $invoice = Invoice::find($data['invoice_id']);
            if ($invoice) {
                if ($data['amount_paid'] >= $invoice->total_amount) {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => $data['payment_date'] ?? now(),
                    ]);
                } else {
                    // Jika pembayaran kurang dari total, kembalikan ke unpaid
                    $invoice->update([
                        'status' => 'unpaid',
                        'paid_at' => null,
                    ]);
                }
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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Kuitansi berhasil diperbarui')
            ->body('Kuitansi telah berhasil diperbarui dan invoice telah disesuaikan.');
    }
}


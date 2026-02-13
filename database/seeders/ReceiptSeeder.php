<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Receipt;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;

class ReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Memulai seeder Receipt (Kwitansi) untuk invoice yang sudah dibayar...');

        // Get first user as creator
        $creator = User::first();
        if (!$creator) {
            $this->command->error('❌ User belum ada di database.');
            return;
        }

        // Get all paid invoices that don't have receipts yet
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereDoesntHave('receipt')
            ->with('student')
            ->get();

        if ($paidInvoices->isEmpty()) {
            $this->command->warn('⚠️  Tidak ada invoice lunas yang perlu dibuatkan kwitansi.');
            return;
        }

        $this->command->info("📊 Ditemukan {$paidInvoices->count()} invoice lunas");

        $totalReceipts = 0;

        foreach ($paidInvoices as $invoice) {
            $methodRoll = rand(1, 100);
            $paymentMethod = match (true) {
                $methodRoll <= 60 => 'va',
                $methodRoll <= 80 => 'bank_transfer',
                $methodRoll <= 90 => 'qris',
                default => 'cash',
            };

            $paymentDate = $invoice->paid_at ?? Carbon::parse($invoice->issued_at)->addDays(rand(1, 10));

            $referenceNumber = null;
            $note = 'Pembayaran diterima.';

            if ($paymentMethod === 'va') {
                $referenceNumber = $invoice->va_number ?: ('BNI' . $paymentDate->format('ymd') . rand(100000, 999999));
                $note = "Pembayaran melalui Virtual Account {$invoice->va_bank} - {$invoice->va_number}";
            } elseif ($paymentMethod === 'bank_transfer') {
                $referenceNumber = 'TRF' . $paymentDate->format('ymd') . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                $note = 'Pembayaran melalui transfer bank.';
            } elseif ($paymentMethod === 'qris') {
                $referenceNumber = 'QRIS' . $paymentDate->format('ymd') . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                $note = 'Pembayaran melalui QRIS.';
            } else {
                $note = 'Pembayaran tunai di loket.';
            }

            Receipt::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->total_amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'payment_date' => $paymentDate,
                'issued_at' => $paymentDate,
                'created_by' => $creator->id,
                'note' => $note,
            ]);

            $totalReceipts++;
        }

        $this->command->info("\n✅ Seeder Receipt selesai!");
        $this->command->info("📊 Total kwitansi dibuat: {$totalReceipts}");
    }
}

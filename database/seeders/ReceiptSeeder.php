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
            // Force payment method to Virtual Account (VA) only
            $paymentMethod = 'va';

            $paymentDate = $invoice->paid_at ?? Carbon::parse($invoice->issued_at)->addDays(rand(1, 10));

            $referenceNumber = null;
            $note = 'Pembayaran diterima.';

            // Use invoice VA if available, otherwise generate a placeholder VA number
            $referenceNumber = $invoice->va_number ?: ('VA' . $paymentDate->format('ymd') . rand(100000, 999999));
            $note = "Pembayaran melalui Virtual Account {$invoice->va_bank} - {$referenceNumber}";

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

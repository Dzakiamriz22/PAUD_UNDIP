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
            // Generate reference number for VA payment
            $referenceNumber = 'BNI' . Carbon::parse($invoice->paid_at)->format('ymd') . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

            Receipt::create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->total_amount,
                'payment_method' => 'va',
                'reference_number' => $referenceNumber,
                'payment_date' => $invoice->paid_at,
                'issued_at' => $invoice->paid_at,
                'created_by' => $creator->id,
                'note' => "Pembayaran melalui Virtual Account BNI - {$invoice->va_number}",
            ]);

            $totalReceipts++;
        }

        $this->command->info("\n✅ Seeder Receipt selesai!");
        $this->command->info("📊 Total kwitansi dibuat: {$totalReceipts}");
    }
}

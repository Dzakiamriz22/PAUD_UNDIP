<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            // Nomor kuitansi unik untuk keperluan audit (format: 000001/BPU/A1.02/RCP/I/2026)
            $table->string('receipt_number')->unique();

            // Relasi ke tabel invoices (One-to-One: 1 invoice hanya punya 1 receipt)
            $table->foreignUuid('invoice_id')
                ->unique()
                ->references('id')
                ->on('invoices')
                ->cascadeOnDelete();

            // Jumlah uang riil yang diterima
            $table->decimal('amount_paid', 12, 2);

            // Metode pembayaran: cash, bank_transfer, va, dll.
            $table->string('payment_method');

            // Nomor referensi (ID Transaksi VA atau nomor referensi bank)
            $table->string('reference_number')->nullable();

            // Tanggal pembayaran (Source of Truth untuk laporan arus kas)
            $table->timestamp('payment_date');
            $table->timestamp('issued_at');

            // Staff/Admin yang membuat kuitansi (Akuntabilitas)
            $table->foreignUuid('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Catatan tambahan (Opsional)
            $table->text('note')->nullable();

            $table->timestamps();

            // Indeks untuk mempercepat pencarian laporan harian/bulanan
            $table->index(['payment_date', 'payment_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};

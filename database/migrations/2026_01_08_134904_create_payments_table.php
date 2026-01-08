<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('id', 36)->primary(); // ← PAKSA CHAR(36)

            $table->char('invoice_id', 36);
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['VA', 'cash', 'transfer']);
            $table->enum('status', ['pending', 'verified', 'rejected']);
            $table->char('verified_by', 36)->nullable();

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
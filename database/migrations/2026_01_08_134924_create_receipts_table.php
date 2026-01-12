<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('id', 36)->primary();
            $table->char('payment_id', 36);

            $table->string('receipt_number')->unique();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
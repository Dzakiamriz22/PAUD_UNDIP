<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id');
            $table->foreignUuid('income_type_id');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('income_type_id')->references('id')->on('income_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->foreignUuid('student_id');
            $table->foreignUuid('class_id');
            $table->foreignUuid('academic_year_id');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'unpaid', 'paid', 'cancelled']);
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

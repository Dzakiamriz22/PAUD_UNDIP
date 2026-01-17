<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignUuid('income_type_id')->after('academic_year_id');
            $table->string('va_number')->nullable()->after('income_type_id');
            $table->string('va_bank')->nullable()->after('va_number');

            $table->foreign('income_type_id')->references('id')->on('income_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignUuid('income_type_id')->after('academic_year_id');
            $table->string('va_number')->nullable()->after('income_type_id');
            $table->string('va_bank')->nullable()->after('va_number');

            $table->foreign('income_type_id')->references('id')->on('income_types');
        });
    }
};

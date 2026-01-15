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
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('income_type_id');
            $table->string('bank_name');
            $table->string('va_number');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('income_type_id')->references('id')->on('income_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};

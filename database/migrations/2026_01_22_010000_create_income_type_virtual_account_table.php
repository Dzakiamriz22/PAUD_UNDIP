<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('income_type_virtual_account')) {
            return;
        }

        Schema::create('income_type_virtual_account', function (Blueprint $table) {
            $table->id();
            $table->char('income_type_id', 36);
            $table->unsignedBigInteger('virtual_account_id');
            $table->timestamps();

            $table->foreign('income_type_id')->references('id')->on('income_types')->onDelete('cascade');
            $table->foreign('virtual_account_id')->references('id')->on('virtual_accounts')->onDelete('cascade');

            // shorter unique index name to avoid MySQL identifier length issues
            $table->unique(['virtual_account_id', 'income_type_id'], 'ix_va_income_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_type_virtual_account');
    }
};

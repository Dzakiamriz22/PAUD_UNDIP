<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('income_type_id');
            $table->enum('class_category', [
                'TK',
                'KB',
                'TPA_PAUD',
                'TPA_SD',
                'TPA_TK',
                'TPA_KB',
            ]);
            $table->decimal('amount', 12, 2);
            $table->enum('billing_type', [
                'once',
                'monthly',
                'yearly',
                'daily',
                'penalty',
            ]);
            $table->boolean('is_active')->default(true);

            $table->foreignUuid('proposed_by')->nullable();
            $table->foreignUuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('income_type_id')->references('id')->on('income_types');
            $table->foreign('proposed_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
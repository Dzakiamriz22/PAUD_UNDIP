<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariff_approval_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tariff_id')->constrained('tariffs')->cascadeOnDelete();
            $table->string('action', 50);
            $table->enum('from_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->enum('to_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->text('note')->nullable();
            $table->foreignUuid('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['tariff_id', 'acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_approval_histories');
    }
};

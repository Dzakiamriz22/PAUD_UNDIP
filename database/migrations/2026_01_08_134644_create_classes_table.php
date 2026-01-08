<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('category', [
                'TK',
                'KB',
                'TPA_PAUD',
                'TPA_SD',
                'TPA_TK',
                'TPA_KB',
            ]);
            $table->string('code');
            $table->foreignUuid('homeroom_teacher_id')->nullable();
            $table->foreignUuid('academic_year_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id');
            $table->foreignUuid('class_id');
            $table->foreignUuid('academic_year_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_histories');
    }
};
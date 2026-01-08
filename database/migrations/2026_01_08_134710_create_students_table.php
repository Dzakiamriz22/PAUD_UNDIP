<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->string('nis')->unique();
            $table->string('parent_name');
            $table->enum('status', ['active', 'inactive', 'graduated'])
                  ->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
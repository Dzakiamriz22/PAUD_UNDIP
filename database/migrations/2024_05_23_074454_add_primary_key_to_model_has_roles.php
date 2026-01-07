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
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unique(['role_id', 'model_id', 'model_type'], 'role_model_unique');
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->id()->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Drop kolom id (auto-increment), MySQL otomatis drop PK dari kolom id
            $table->dropColumn('id');
        });
        
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Tambahkan kembali composite primary key seperti awal bawaan Spatie
            $table->primary(
                ['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary'
            );

            // Drop unique index yang sudah kita buat sebelumnya
            $table->dropUnique('role_model_unique');
        });
    }
};

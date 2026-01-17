<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['income_type_id']);
            $table->dropForeign(['class_id']);

            $table->dropColumn(['income_type_id', 'class_id']);

            // 3. Tambahkan kolom audit pendapatan
            $table->timestamp('paid_at')->after('due_date')->nullable();
            $table->decimal('total_amount', 12, 2)->change();

            $table->decimal('discount_amount', 12, 2)->after('total_amount')->default(0);
            $table->decimal('sub_total', 12, 2)->after('total_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 1. Tambahkan kembali kolom yang sebelumnya dihapus
            // Gunakan ->nullable() agar tidak error jika sudah ada data
            $table->char('income_type_id', 36)->nullable();
            $table->char('class_id', 36)->nullable();

            // 2. Buat kembali Foreign Key
            // PASTIKAN nama tabel di ->on(...) sesuai dengan yang ada di database Anda
            $table->foreign('income_type_id')->references('id')->on('income_types');

            // Coba ganti 'school_classes' menjadi 'classes' jika itu nama tabel aslinya
            $table->foreign('class_id')->references('id')->on('classes');

            // 3. Hapus kolom yang ditambahkan saat up()
            $table->dropColumn(['paid_at', 'discount_amount', 'sub_total']);
        });
    }
};

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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->date('period_day')->after('tariff_id')->nullable();
            $table->integer('period_month')->after('period_day')->nullable();
            $table->integer('period_year')->after('period_month')->nullable();  
            $table->dropColumn('original_amount','discount_amount');      
        });  
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'period_month',
                'period_year', 
                'period_day',
            ]);
            $table->decimal('original_amount', 12, 2)->after('tariff_id');
            $table->decimal('discount_amount', 12, 2)->after('original_amount')->default(0);
        });
    }
};

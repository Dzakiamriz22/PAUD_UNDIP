<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropForeign(['proposed_by']);
            $table->dropForeign(['approved_by']);

            $table->foreignUuid('proposed_by')->nullable()->change();
            $table->foreignUuid('approved_by')->nullable()->change();

            $table->foreign('proposed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropForeign(['proposed_by']);
            $table->dropForeign(['approved_by']);

            $table->foreign('proposed_by')
                ->references('id')
                ->on('users');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users');
        });
    }
};

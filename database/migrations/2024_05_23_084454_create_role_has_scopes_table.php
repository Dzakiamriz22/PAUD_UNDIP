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
        Schema::create('role_has_scopes', function (Blueprint $table) {
            $table->id();

            // FK ke model_has_roles yang sekarang sudah punya ID
            $table->unsignedBigInteger('model_role_id');

            // Scope polymorphic
            $table->string('scope_type');
            $table->uuid('scope_id');

            $table->timestamps();

            $table->foreign('model_role_id')
                  ->references('id')
                  ->on('model_has_roles')
                  ->onDelete('cascade');

            // Uniqueness constraint (prevent duplicate role-scope assign)
            $table->unique(['model_role_id', 'scope_type', 'scope_id'], 'unique_role_scope');

            // Optional: still keep index for search performance
            $table->index(['scope_type', 'scope_id'], 'scope_polymorphic_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_has_scopes');
    }
};

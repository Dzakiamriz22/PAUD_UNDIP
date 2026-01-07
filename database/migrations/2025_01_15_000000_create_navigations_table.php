<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            // $table->treeColumns();

            // $table->foreignId('parent_id')->nullable()->constrained('navigations'); // M
            $table->integer('parent_id')->default(-1)->index(); // Must default to -1!
            $table->integer('order')->default(0);
            $table->string('title');

            $table->foreignId('menu_id')->constrained('menus');

            // $table->foreignId('parent_id')->nullable()->constrained('navigations');
            // $table->string('title');
            $table->string('link_type')->default('url');
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            // $table->integer('sort')->default(1);
            $table->string('css_class')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            // $table->softDeletes();

            // $table->index('parent_id');
            // $table->index('sort');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};

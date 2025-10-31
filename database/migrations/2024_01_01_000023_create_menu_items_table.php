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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu_location')->default('header'); // header, footer, sidebar
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('route')->nullable(); // Laravel route name
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // เงื่อนไขแสดงผล (logged_in, role, etc.)
            $table->timestamps();

            $table->index(['menu_location', 'order']);
            $table->index('parent_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};

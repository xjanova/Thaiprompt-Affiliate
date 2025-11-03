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
        Schema::create('article_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('learning_articles')->onDelete('cascade');
            $table->enum('permission_type', ['role', 'user'])->default('role');
            $table->string('permission_value')->comment('Role name or user ID');
            $table->timestamps();

            $table->index(['article_id', 'permission_type']);
            $table->index('permission_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_permissions');
    }
};

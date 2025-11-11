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
        Schema::create('user_taskbar_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('icon', 10); // Emoji หรือ icon character
            $table->string('label', 100); // ชื่อเมนู
            $table->string('url', 500); // URL ของเมนู
            $table->integer('order')->default(0); // ลำดับการแสดง
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'order']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_taskbar_shortcuts');
    }
};

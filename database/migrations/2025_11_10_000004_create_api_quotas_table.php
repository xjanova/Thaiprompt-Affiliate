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
        Schema::create('api_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->string('period_type'); // minute, hour, day, month
            $table->string('period_value'); // 2025-11-10-14 สำหรับ hour, 2025-11-10 สำหรับ day
            $table->integer('request_count')->default(0); // จำนวนการใช้งาน
            $table->integer('limit')->nullable(); // ลิมิตของช่วงเวลานี้
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['api_key_id', 'period_type', 'period_value']);
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_quotas');
    }
};

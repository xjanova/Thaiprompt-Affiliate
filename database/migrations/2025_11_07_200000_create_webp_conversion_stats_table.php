<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webp_conversion_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('total_converted')->default(0)->comment('รวมจำนวนรูปที่แปลงแล้ว');
            $table->integer('total_from_uploads')->default(0)->comment('จำนวนจากการอัพโหลดอัตโตมัติ');
            $table->integer('total_from_batch')->default(0)->comment('จำนวนจากการแปลงกลุ่ม');
            $table->timestamp('last_upload_conversion')->nullable()->comment('เวลาแปลงล่าสุดจากการอัพโหลด');
            $table->timestamp('last_batch_conversion')->nullable()->comment('เวลาแปลงล่าสุดจากการแปลงกลุ่ม');
            $table->timestamps();
        });

        // Insert initial record
        DB::table('webp_conversion_stats')->insert([
            'total_converted' => 0,
            'total_from_uploads' => 0,
            'total_from_batch' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webp_conversion_stats');
    }
};

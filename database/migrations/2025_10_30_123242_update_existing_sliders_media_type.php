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
        // อัพเดตสไลด์เก่าที่ยังไม่มี media_type ให้เป็น 'image'
        DB::table('sliders')
            ->whereNull('media_type')
            ->orWhere('media_type', '')
            ->update(['media_type' => 'image']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ไม่ต้อง rollback เพราะเป็นการอัพเดตข้อมูล
    }
};

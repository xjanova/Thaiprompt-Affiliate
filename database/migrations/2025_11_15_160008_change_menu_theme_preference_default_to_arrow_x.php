<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * เปลี่ยน default value ของ menu_theme_preference เป็น arrow-x
     * และอัพเดทผู้ใช้ที่มีอยู่แล้วให้ใช้ arrow-x ทั้งหมด
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าคอลัมน์มีอยู่หรือไม่
        if (!Schema::hasColumn('users', 'menu_theme_preference')) {
            return;
        }

        // อัพเดทผู้ใช้ทั้งหมดให้ใช้ arrow-x
        DB::table('users')->update([
            'menu_theme_preference' => 'arrow-x'
        ]);

        // เปลี่ยน default value ของคอลัมน์เป็น arrow-x
        Schema::table('users', function (Blueprint $table) {
            $table->string('menu_theme_preference')
                ->default('arrow-x')
                ->change();
        });
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง
     *
     * @return void
     */
    public function down(): void
    {
        // ตรวจสอบว่าคอลัมน์มีอยู่หรือไม่
        if (!Schema::hasColumn('users', 'menu_theme_preference')) {
            return;
        }

        // เปลี่ยน default value กลับเป็น millennium
        Schema::table('users', function (Blueprint $table) {
            $table->string('menu_theme_preference')
                ->default('millennium')
                ->change();
        });

        // หมายเหตุ: ไม่ย้อนกลับการอัพเดทข้อมูลผู้ใช้
        // เพราะอาจทำให้เกิดปัญหากับข้อมูลที่มีอยู่
    }
};

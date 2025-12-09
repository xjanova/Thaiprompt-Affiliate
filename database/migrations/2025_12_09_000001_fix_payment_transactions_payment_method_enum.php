<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * แก้ไขปัญหา payment_method enum ที่ไม่ครอบคลุม payment methods ทั้งหมด
 *
 * ปัญหา: enum มีแค่ 5 ค่า (wallet, promptpay, credit_card, bank_transfer, cash_on_delivery)
 * แต่ระบบรองรับ payment methods มากกว่านั้น เช่น stripe, paypal, omise, truemoney
 *
 * วิธีแก้: เปลี่ยนจาก ENUM เป็น VARCHAR เพื่อความยืดหยุ่น
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่
        if (!Schema::hasTable('payment_transactions')) {
            return;
        }

        // เปลี่ยนคอลัมน์ payment_method จาก ENUM เป็น VARCHAR
        // ใช้ raw SQL เพราะ Laravel ไม่รองรับการเปลี่ยน ENUM โดยตรง
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN payment_method VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่ควร rollback เพราะจะทำให้ข้อมูลหาย
        // ถ้าจำเป็นต้อง rollback ให้ทำ manual
    }
};

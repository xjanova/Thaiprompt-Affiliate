<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์กำกับ "ของสายมู" + ด่านอนุมัติ ลงตาราง marketplace_products
     *
     * 🚨 ทำไมต้องมี `mu_group` ทั้งที่มีคอลัมน์ `source` อยู่แล้ว
     *   ตรวจพร็อดวันที่ 2026-08-22: `source = 'mu_curated'` มี **900 แถว**
     *   แต่ไฟล์คัดสรร `database/data/lazada-mu-products.json` มีแค่ **105 id**
     *   ⇒ 795 แถวถูกแปะป้ายสายมูทั้งที่ไม่ใช่ (แฟ้ม A4, สายชาร์จ, เวเฟอร์, เจลลี่ลดน้ำหนัก)
     *   ถ้าบอทกรองด้วย `source` แล้วลูกค้าถามเรื่องแก้ปีชง แม่หมอจะส่ง "สายชาร์จ 105 บาท" ไปให้
     *   ⇒ ต้องมีตัวมาร์คที่ **เขียนตอนนำเข้าและมีคนตรวจ** ไม่ใช่เดาจาก source หรือค้นชื่อ
     *
     * 🚨 ทำไมค้นจากชื่อไม่ได้ (เคยลองแล้ว)
     *   ใน 10 อันดับค่าคอมสูงสุดของสายมูจริง มี 6 ชิ้นชื่ออังกฤษล้วน
     *   (`Pixiu Tiger's Eye Stone`, `Nobel - Pi Xiu Bracelet`, `Jadeite Zodiac`)
     *   ⇒ `LIKE '%ปี่เซี้ยะ%'` ไม่โดนสักตัว
     *
     * ⚠️ ห้ามใส่ `Schema::hasTable() + return` ในไมเกรชันแบบเพิ่มคอลัมน์
     *   (ตารางมีอยู่แล้วเสมอ ⇒ จะ return ออกก่อนสร้างคอลัมน์)
     */
    public function up(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $this->safeAddColumn($table, 'marketplace_products', 'mu_group', function ($table) {
                $table->string('mu_group', 32)->nullable()
                    ->comment('pixiu | pyramid | pichong | zodiac | charm — null = ไม่ใช่ของสายมู');
            });

            $this->safeAddColumn($table, 'marketplace_products', 'mu_keyword_id', function ($table) {
                $table->unsignedBigInteger('mu_keyword_id')->nullable()
                    ->comment('มาจากคีย์เวิร์ดไหน (lazada_mu_keywords.id) — ไว้ตามรอยที่มา');
            });

            // ✅ default 'approved' โดยตั้งใจ — แถวเดิม 1,109 ชิ้นขายอยู่บนหน้าร้านแล้ว
            //    ถ้า default เป็น pending ของบนร้านจะหายทั้งหมดทันทีที่รันไมเกรชัน
            //    เส้นทางใหม่ (บอทค้นสดให้ลูกค้า) จะ set 'pending' เองอย่างชัดเจนในโค้ด
            $this->safeAddColumn($table, 'marketplace_products', 'approval_status', function ($table) {
                $table->string('approval_status', 16)->default('approved')
                    ->comment('approved = ขึ้นร้านได้ | pending = รอคนตรวจ | rejected = ตีกลับ');
            });

            $this->safeAddColumn($table, 'marketplace_products', 'mu_verified_at', function ($table) {
                $table->timestamp('mu_verified_at')->nullable()
                    ->comment('คนกดยืนยันว่าเป็นของจริงตรงหมวดเมื่อไหร่');
            });

            $this->safeAddColumn($table, 'marketplace_products', 'rejected_reason', function ($table) {
                $table->string('rejected_reason', 191)->nullable()
                    ->comment('เหตุผลที่ตีกลับ — ราคาเกิน / ค่าคอมต่ำ / ติด blocklist / คนปฏิเสธ');
            });
        });

        $this->safeAddIndex('marketplace_products', 'mu_group', 'mp_mu_group_idx');
        $this->safeAddIndex('marketplace_products', 'approval_status', 'mp_approval_status_idx');
        $this->safeAddIndex('marketplace_products', 'mu_keyword_id', 'mp_mu_keyword_idx');
    }

    /**
     * ลบคอลัมน์ที่เพิ่มไป
     */
    public function down(): void
    {
        $this->safeDropIndex('marketplace_products', 'mp_mu_group_idx');
        $this->safeDropIndex('marketplace_products', 'mp_approval_status_idx');
        $this->safeDropIndex('marketplace_products', 'mp_mu_keyword_idx');

        $this->safeDropColumn('marketplace_products', [
            'mu_group',
            'mu_keyword_id',
            'approval_status',
            'mu_verified_at',
            'rejected_reason',
        ]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🏦 (2026-07-14) เพิ่มการตั้งค่า "ตรวจสลิปด้วย KBank API" ในตาราง fortune_telling_settings
     *
     * เป็น provider ตรวจสลิปตัวที่ 2 (คู่ขนานกับ SlipOK) — ยิงถาม ledger ของ KBank โดยตรง
     * ผ่าน Slip Verification API (OAuth2 + Two-Way SSL/mTLS) เหมาะกับเงินเข้าบัญชี KBank
     *
     * ⚠️ ADD COLUMN — ห้ามใช้ Schema::hasTable()+return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
     *    ใช้ Schema::table() + เช็คทีละคอลัมน์แทน
     *
     * default = ปิดทั้งหมด (แอดมินต้องกรอก Consumer Key/Secret + cert + เปิดสวิตช์ก่อนถึงจะทำงาน)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิดการตรวจสลิปด้วย KBank
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_kbank_verify')) {
                $table->boolean('enable_kbank_verify')->default(false)->comment('เปิดตรวจสลิปด้วย KBank API');
            }

            // สภาพแวดล้อม: sandbox (ทดสอบ) หรือ production
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_env')) {
                $table->string('kbank_env', 20)->default('sandbox')->comment('sandbox|production');
            }

            // Base URL override (เว้นว่าง = เลือกอัตโนมัติจาก kbank_env)
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_base_url')) {
                $table->string('kbank_base_url', 255)->nullable()->comment('override base URL (ปกติเว้นว่าง)');
            }

            // Path ของ endpoint ตรวจสลิป (เผื่อ KBank เปลี่ยน version — แอดมินแก้ได้โดยไม่ต้องแก้โค้ด)
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_verify_path')) {
                $table->string('kbank_verify_path', 255)->nullable()->comment('เช่น /v1/verslip/kbank/verify');
            }

            // OAuth2 client credentials
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_consumer_id')) {
                $table->string('kbank_consumer_id', 191)->nullable()->comment('Consumer ID / Key จาก KBank API Portal');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_consumer_secret')) {
                // เก็บแบบเข้ารหัส (cast: encrypted) — token ยาว → ใช้ text
                $table->text('kbank_consumer_secret')->nullable()->comment('Consumer Secret (เข้ารหัสเก็บ)');
            }

            // Two-Way SSL (mTLS) — path ของ client cert / private key บนเซิร์ฟเวอร์
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_cert_path')) {
                $table->string('kbank_cert_path', 500)->nullable()->comment('path ไฟล์ client cert (.pem/.crt)');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_cert_key_path')) {
                $table->string('kbank_cert_key_path', 500)->nullable()->comment('path ไฟล์ private key (.key)');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_cert_password')) {
                $table->text('kbank_cert_password')->nullable()->comment('passphrase ของ private key (เข้ารหัสเก็บ)');
            }

            // ยอดขั้นต่ำที่อนุมัติได้ (บาท)
            if (! Schema::hasColumn('fortune_telling_settings', 'kbank_min_amount')) {
                $table->decimal('kbank_min_amount', 10, 2)->default(99.00)->comment('ยอดขั้นต่ำที่อนุมัติ');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'enable_kbank_verify',
                'kbank_env',
                'kbank_base_url',
                'kbank_verify_path',
                'kbank_consumer_id',
                'kbank_consumer_secret',
                'kbank_cert_path',
                'kbank_cert_key_path',
                'kbank_cert_password',
                'kbank_min_amount',
            ] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

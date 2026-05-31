<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🧾 (2026-05-31) SlipOK Slip Verification — fallback ชั้นสองเมื่อ SMS checker ไม่พบ
 *
 * แนวคิด (user spec):
 *   - SMS checker = ตัวหลัก (ฟรี) ตัดบิลก่อนเสมอ
 *   - SlipOK = ตัวสำรอง เรียกเฉพาะตอน "ลูกค้าส่งสลิปแล้ว 1 นาทียังไม่ตัด" หรือ "ลูกค้าพิมพ์/ถามเข้ามา"
 *     → ประหยัดโควตา SlipOK (เรียกเมื่อ SMS น่าจะมีปัญหาเท่านั้น)
 *   - อนุมัติถ้า verify จริง + บัญชีปลายทาง=ของเรา + ยอด ≥ ขั้นต่ำ (99) + transRef ไม่ซ้ำ
 *
 * Settings (fortune_telling_settings):
 *   - enable_slipok_verify: kill switch (default false — admin เปิดเอง)
 *   - slipok_branch_id: Branch ID ใส่ใน URL path
 *   - slipok_api_key: API Key (header x-authorization) — เข้ารหัสเก็บ
 *   - slipok_min_amount: ยอดขั้นต่ำที่อนุมัติ (default 99 — "ไม่ต่ำกว่า 99")
 *   - slipok_fallback_delay_seconds: หน่วงก่อนเช็ค fallback (default 60 = 1 นาที)
 *   - slipok_use_log: ส่ง log=true ให้ SlipOK (dedup + เช็คบัญชีผู้รับ + ส่งซ้ำไม่กินโควตา)
 *
 * fortune_readings (transient + audit):
 *   - slip_image_path: ที่เก็บสลิปที่ลูกค้าส่ง (รอ verify ตอน fallback) — ลบหลัง verify
 *   - slip_received_at: เวลาที่รับสลิป
 *   - slipok_verified_at: เวลาที่ SlipOK อนุมัติ
 *   - slipok_trans_ref: เลขอ้างอิงรายการจาก SlipOK
 *
 * slip_verifications: audit + dedup (trans_ref unique กันสลิปซ้ำข้ามบิล)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_telling_settings', 'enable_slipok_verify')) {
                    $table->boolean('enable_slipok_verify')
                        ->default(false)
                        ->comment('เปิดใช้ SlipOK ตรวจสลิป (fallback เมื่อ SMS checker ไม่พบ)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'slipok_branch_id')) {
                    $table->string('slipok_branch_id', 64)->nullable()
                        ->comment('SlipOK Branch ID (ใส่ใน URL path)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'slipok_api_key')) {
                    $table->text('slipok_api_key')->nullable()
                        ->comment('SlipOK API Key (header x-authorization) — encrypted');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'slipok_min_amount')) {
                    $table->decimal('slipok_min_amount', 8, 2)->default(99.00)
                        ->comment('ยอดขั้นต่ำที่อนุมัติ (ไม่ต่ำกว่านี้) default 99');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'slipok_fallback_delay_seconds')) {
                    $table->unsignedInteger('slipok_fallback_delay_seconds')->default(60)
                        ->comment('หน่วงก่อนเช็ค fallback (วินาที) default 60');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'slipok_use_log')) {
                    $table->boolean('slipok_use_log')->default(true)
                        ->comment('ส่ง log=true ให้ SlipOK (dedup + เช็คบัญชีผู้รับ)');
                }
            });
        }

        if (Schema::hasTable('fortune_readings')) {
            Schema::table('fortune_readings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_readings', 'slip_image_path')) {
                    $table->string('slip_image_path', 500)->nullable()
                        ->comment('สลิปที่ลูกค้าส่ง (รอ verify fallback) — ลบหลังตรวจเสร็จ');
                }
                if (! Schema::hasColumn('fortune_readings', 'slip_received_at')) {
                    $table->timestamp('slip_received_at')->nullable()
                        ->comment('เวลาที่รับสลิปจากลูกค้า');
                }
                if (! Schema::hasColumn('fortune_readings', 'slipok_verified_at')) {
                    $table->timestamp('slipok_verified_at')->nullable()
                        ->comment('เวลาที่ SlipOK อนุมัติบิลนี้');
                }
                if (! Schema::hasColumn('fortune_readings', 'slipok_trans_ref')) {
                    $table->string('slipok_trans_ref', 64)->nullable()
                        ->comment('เลขอ้างอิงรายการ (transRef) จาก SlipOK');
                }
            });
        }

        if (! Schema::hasTable('slip_verifications')) {
            Schema::create('slip_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('trans_ref', 64)->unique()
                    ->comment('เลขอ้างอิงรายการ — unique กันสลิปซ้ำข้ามบิล');
                $table->unsignedBigInteger('fortune_reading_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('sending_bank', 8)->nullable();
                $table->string('receiving_bank', 8)->nullable();
                $table->string('receiver_account', 64)->nullable()->comment('บัญชีปลายทาง (masked)');
                $table->string('sender_name', 120)->nullable();
                $table->string('status', 24)->default('verified')->comment('verified/rejected/duplicate');
                $table->json('raw')->nullable()->comment('response ดิบจาก SlipOK (audit)');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['fortune_reading_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                foreach ([
                    'enable_slipok_verify', 'slipok_branch_id', 'slipok_api_key',
                    'slipok_min_amount', 'slipok_fallback_delay_seconds', 'slipok_use_log',
                ] as $c) {
                    if (Schema::hasColumn('fortune_telling_settings', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('fortune_readings')) {
            Schema::table('fortune_readings', function (Blueprint $table) {
                foreach (['slip_image_path', 'slip_received_at', 'slipok_verified_at', 'slipok_trans_ref'] as $c) {
                    if (Schema::hasColumn('fortune_readings', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        Schema::dropIfExists('slip_verifications');
    }
};

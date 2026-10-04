<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🪪 AI eKYC (2026-10-04) — คอลัมน์ใหม่ใน kyc_verifications + ตารางบันทึกการเปิดดูรูป (PDPA)
 *
 * 1 แถว kyc_verifications = 1 รอบยืนยัน (method = 'ekyc') หรือคำขอแบบเดิม (method = 'manual')
 *   - รูปเก็บบน private disk แบบเข้ารหัส (Crypt) — id_card_image = รูปบัตร · card_face_path = หน้าจากบัตร
 *     best_frame_path = เฟรมหน้าที่ดีที่สุด (เฟรมอื่นไม่ถูกเก็บ)
 *   - เลขบัตรเก็บแบบเข้ารหัส (id_number_encrypted) + HMAC (id_number_hash ไว้หาบัตรซ้ำ) + 4 ตัวท้าย
 *   - ekyc_challenges / ekyc_expires_at / ekyc_step = สถานะรอบยืนยันฝั่งเซิร์ฟเวอร์ (คำสั่งสุ่มเก็บใน DB ไม่ใช่แคช)
 *
 * kyc_access_logs = ทุกครั้งที่แอดมินเปิดดูรูปบัตร/ใบหน้า (หลักฐานตาม PDPA)
 *
 * ⚠️ prod เป็น MariaDB: คอลัมน์ timestamp ทุกตัวต้อง nullable (กัน ON UPDATE CURRENT_TIMESTAMP)
 * เพิ่มคอลัมน์อย่างเดียว ไม่แตะคอลัมน์เดิม · รันซ้ำได้ (SafeMigration)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (Schema::hasTable('kyc_verifications')) {
            Schema::table('kyc_verifications', function (Blueprint $table) {
                $t = 'kyc_verifications';

                // ช่องทาง: manual = อัปโหลดรูปให้แอดมินตรวจ (แบบเดิม) · ekyc = AI ตรวจ (แถวเดิมทั้งหมด = manual)
                $this->safeAddColumn($table, $t, 'method', fn ($c) => $c->string('method', 10)->default('manual'));
                // รหัสรอบยืนยัน (uuid) ที่แอปใช้อ้างถึง
                $this->safeAddColumn($table, $t, 'ekyc_session_id', fn ($c) => $c->string('ekyc_session_id', 36)->nullable());
                // ยินยอมเก็บข้อมูลชีวภาพ (PDPA) เมื่อไร + ข้อความยินยอมเวอร์ชันไหน
                $this->safeAddColumn($table, $t, 'consent_at', fn ($c) => $c->timestamp('consent_at')->nullable());
                $this->safeAddColumn($table, $t, 'consent_version', fn ($c) => $c->string('consent_version', 20)->nullable());

                // ข้อมูลจากบัตร (เลขบัตรเข้ารหัส · hash ไว้หาบัตรซ้ำ · 4 ตัวท้ายไว้แสดง)
                $this->safeAddColumn($table, $t, 'id_number_encrypted', fn ($c) => $c->text('id_number_encrypted')->nullable());
                $this->safeAddColumn($table, $t, 'id_number_hash', fn ($c) => $c->string('id_number_hash', 64)->nullable());
                $this->safeAddColumn($table, $t, 'id_last4', fn ($c) => $c->string('id_last4', 4)->nullable());
                $this->safeAddColumn($table, $t, 'name_th', fn ($c) => $c->string('name_th', 191)->nullable());
                $this->safeAddColumn($table, $t, 'name_en', fn ($c) => $c->string('name_en', 191)->nullable());
                $this->safeAddColumn($table, $t, 'birth_date', fn ($c) => $c->date('birth_date')->nullable());
                $this->safeAddColumn($table, $t, 'card_expiry', fn ($c) => $c->date('card_expiry')->nullable());

                // ผลจาก AI
                $this->safeAddColumn($table, $t, 'ai_decision', fn ($c) => $c->string('ai_decision', 10)->nullable());
                $this->safeAddColumn($table, $t, 'ai_reasons', fn ($c) => $c->json('ai_reasons')->nullable());
                // cosine ของ SFace (-1..1) — ค่าที่ใช้ตัดสินจริง
                $this->safeAddColumn($table, $t, 'ai_face_match', fn ($c) => $c->decimal('ai_face_match', 6, 4)->nullable());
                $this->safeAddColumn($table, $t, 'ai_liveness', fn ($c) => $c->decimal('ai_liveness', 5, 4)->nullable());
                $this->safeAddColumn($table, $t, 'ai_real', fn ($c) => $c->decimal('ai_real', 5, 4)->nullable());
                $this->safeAddColumn($table, $t, 'ai_card_real', fn ($c) => $c->decimal('ai_card_real', 5, 4)->nullable());
                $this->safeAddColumn($table, $t, 'ai_ocr_confidence', fn ($c) => $c->decimal('ai_ocr_confidence', 5, 4)->nullable());
                $this->safeAddColumn($table, $t, 'ai_model_version', fn ($c) => $c->string('ai_model_version', 120)->nullable());

                // รูปที่เก็บไว้หลังตัดสิน (เข้ารหัส · private disk)
                $this->safeAddColumn($table, $t, 'card_face_path', fn ($c) => $c->string('card_face_path', 255)->nullable());
                $this->safeAddColumn($table, $t, 'best_frame_path', fn ($c) => $c->string('best_frame_path', 255)->nullable());
                $this->safeAddColumn($table, $t, 'processed_at', fn ($c) => $c->timestamp('processed_at')->nullable());

                // สถานะรอบยืนยันฝั่งเซิร์ฟเวอร์: คำสั่งสุ่ม 3 ข้อ (เรียงตามลำดับ) · หมดอายุเมื่อไร · ถึงขั้นไหนแล้ว
                $this->safeAddColumn($table, $t, 'ekyc_challenges', fn ($c) => $c->json('ekyc_challenges')->nullable());
                $this->safeAddColumn($table, $t, 'ekyc_expires_at', fn ($c) => $c->timestamp('ekyc_expires_at')->nullable());
                $this->safeAddColumn($table, $t, 'ekyc_step', fn ($c) => $c->string('ekyc_step', 20)->nullable());
            });

            $this->safeAddIndex('kyc_verifications', 'ekyc_session_id', 'kyc_ekyc_session_unique', 'unique');
            $this->safeAddIndex('kyc_verifications', 'id_number_hash', 'kyc_id_number_hash_idx');
            $this->safeAddIndex('kyc_verifications', ['method', 'status'], 'kyc_method_status_idx');
            $this->safeAddIndex('kyc_verifications', ['user_id', 'ai_decision', 'processed_at'], 'kyc_user_decision_idx');
        }

        // บันทึกการเปิดดูรูป KYC ของแอดมิน (PDPA) — ห้ามลบ/แก้จากหน้าเว็บ
        if (! Schema::hasTable('kyc_access_logs')) {
            Schema::create('kyc_access_logs', function (Blueprint $table) {
                $table->id();
                // ไม่ผูก foreign key กับ kyc_verifications — แถว KYC ถูกลบตอนลบบัญชีได้ แต่บันทึกการเข้าถึงต้องอยู่ต่อ
                $table->unsignedBigInteger('kyc_verification_id')->index();
                $table->unsignedBigInteger('subject_user_id')->nullable()->index();
                $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
                // card | card_face | best_frame
                $table->string('kind', 20);
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_access_logs');

        if (Schema::hasTable('kyc_verifications')) {
            $this->safeDropIndex('kyc_verifications', 'kyc_user_decision_idx');
            $this->safeDropIndex('kyc_verifications', 'kyc_method_status_idx');
            $this->safeDropIndex('kyc_verifications', 'kyc_id_number_hash_idx');
            if ($this->indexExists('kyc_verifications', 'kyc_ekyc_session_unique')) {
                Schema::table('kyc_verifications', fn (Blueprint $table) => $table->dropUnique('kyc_ekyc_session_unique'));
            }

            $this->safeDropColumn('kyc_verifications', [
                'method', 'ekyc_session_id', 'consent_at', 'consent_version',
                'id_number_encrypted', 'id_number_hash', 'id_last4', 'name_th', 'name_en', 'birth_date', 'card_expiry',
                'ai_decision', 'ai_reasons', 'ai_face_match', 'ai_liveness', 'ai_real', 'ai_card_real',
                'ai_ocr_confidence', 'ai_model_version', 'card_face_path', 'best_frame_path', 'processed_at',
                'ekyc_challenges', 'ekyc_expires_at', 'ekyc_step',
            ]);
        }
    }
};

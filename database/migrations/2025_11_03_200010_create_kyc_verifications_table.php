<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: สร้างตาราง kyc_verifications และเพิ่มฟิลด์ KYC ในตาราง users
 *
 * ⚠️ NOTE: ฟิลด์ KYC ในตาราง users ถูกรวมใน create_users_comprehensive_v3 แล้ว
 * Migration นี้จะข้ามส่วนที่เกี่ยวกับ users ถ้าตารางยังไม่มี
 */
return new class extends Migration
{
    /**
     * สร้างตาราง kyc_verifications และเพิ่มฟิลด์ KYC
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        // ถ้าไม่มี ให้ข้าม migration นี้ทั้งหมด
        // เพราะ kyc_verifications ต้องมี foreign key ไปยัง users
        if (!Schema::hasTable('users')) {
            // ตาราง users ยังไม่มี - ข้าม migration นี้
            // kyc_verifications และ kyc fields จะถูกสร้างในภายหลัง
            return;
        }

        // สร้างตาราง kyc_verifications (ถ้ายังไม่มี)
        if (!Schema::hasTable('kyc_verifications')) {
            Schema::create('kyc_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('id_card_image'); // Path to ID card photo
                $table->string('selfie_image'); // Path to selfie with ID card
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                // Index for faster queries
                $table->index('user_id');
                $table->index('status');
            });
        }

        // เพิ่มฟิลด์ kyc_status ในตาราง users (ถ้ายังไม่มี)
        // ฟิลด์เหล่านี้อาจมีอยู่แล้วจาก users_comprehensive migration
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])
                    ->default('not_submitted')
                    ->after('is_super_admin');
            }
            if (!Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            }
        });
    }

    /**
     * ลบตาราง kyc_verifications และฟิลด์ที่เกี่ยวข้อง
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('users', 'kyc_status')) {
                    $columns[] = 'kyc_status';
                }
                if (Schema::hasColumn('users', 'kyc_verified_at')) {
                    $columns[] = 'kyc_verified_at';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('kyc_verifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_clusters สำหรับจัดกลุ่มคำสำคัญที่เกี่ยวข้องกัน
     *
     * Cluster ช่วย:
     * - จัดการคำสำคัญที่มีความหมายคล้ายกัน
     * - ขยายการตรวจจับคำสำคัญอัตโนมัติ
     * - สร้างข้อเสนอแนะที่เกี่ยวข้อง
     * - วิเคราะห์รูปแบบการใช้งาน
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('keyword_clusters')) {
            return;
        }

        Schema::create('keyword_clusters', function (Blueprint $table) {
            $table->id();

            // Cluster Information
            $table->string('cluster_name')->unique()->index(); // เช่น "shipping_issues", "payment_problems"
            $table->string('display_name'); // ชื่อแสดง เช่น "ปัญหาการจัดส่ง"
            $table->text('description')->nullable(); // คำอธิบาย
            $table->string('icon')->nullable(); // ไอคอน (เช่น "truck", "credit-card")

            // Cluster Classification
            $table->enum('cluster_category', [
                'PRODUCT',
                'SERVICE',
                'ISSUE',
                'FEATURE',
                'FEEDBACK',
                'PROCESS',
                'TECHNICAL',
                'BUSINESS',
                'OTHER'
            ])->index();

            // Metadata
            $table->integer('keyword_count')->default(0); // จำนวนคำสำคัญในกลุ่ม
            $table->json('primary_keywords')->nullable(); // คำสำคัญหลัก 3-5 ตัว
            $table->json('related_intents')->nullable(); // Intent ที่เกี่ยวข้อง
            $table->json('related_entities')->nullable(); // Entity types ที่เกี่ยวข้อง
            $table->json('suggested_actions')->nullable(); // แนะนำการดำเนินการ

            // Analytics
            $table->integer('total_matches')->default(0); // จำนวนครั้งที่พบ
            $table->float('usage_frequency')->default(0); // ความถี่การใช้ (0-1)
            $table->integer('days_since_last_match')->nullable(); // วันตั้งแต่พบครั้งสุดท้าย

            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false); // คลัสเตอร์ที่ระบบสร้างมา
            $table->integer('created_by_user_id')->nullable(); // ผู้สร้าง
            $table->integer('last_modified_by_user_id')->nullable(); // ผู้แก้ไขครั้งล่าสุด

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['cluster_category', 'is_active']);
            $table->index(['usage_frequency', 'total_matches']);
        });
    }

    /**
     * ลบตาราง keyword_clusters
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_clusters');
    }
};

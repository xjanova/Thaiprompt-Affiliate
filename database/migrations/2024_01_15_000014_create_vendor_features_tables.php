<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง vendor_features - ฟีเจอร์ที่มีให้ซื้อ
        Schema::create('vendor_features', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อฟีเจอร์
            $table->string('slug')->unique(); // URL-friendly name
            $table->text('description'); // คำอธิบายฟีเจอร์
            $table->text('short_description')->nullable(); // คำอธิบายสั้น
            $table->decimal('price', 10, 2)->default(0); // ราคาฟีเจอร์
            $table->string('price_type')->default('one_time'); // one_time, monthly, yearly
            $table->string('current_version')->default('1.0.0'); // เวอร์ชันปัจจุบัน
            $table->json('features_list')->nullable(); // รายการคุณสมบัติ (array)
            $table->string('icon')->nullable(); // ไอคอนฟีเจอร์
            $table->string('category')->nullable(); // หมวดหมู่ (sales, marketing, analytics, etc.)
            $table->integer('sort_order')->default(0); // ลำดับการแสดง
            $table->boolean('is_active')->default(true); // เปิด/ปิดการขาย
            $table->boolean('is_featured')->default(false); // แนะนำ
            $table->integer('max_purchases')->nullable(); // จำนวนครั้งที่ซื้อได้สูงสุด (null = unlimited)
            $table->json('requirements')->nullable(); // ความต้องการ (ฟีเจอร์อื่นที่ต้องมีก่อน)
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category']);
            $table->index('sort_order');
        });

        // ตาราง vendor_feature_versions - ประวัติเวอร์ชันของฟีเจอร์
        Schema::create('vendor_feature_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_feature_id')->constrained()->cascadeOnDelete();
            $table->string('version'); // เลขเวอร์ชัน (1.0.0, 1.1.0, etc.)
            $table->text('release_notes'); // รายละเอียดการอัพเดท
            $table->json('changes')->nullable(); // รายการการเปลี่ยนแปลง (array)
            $table->string('change_type')->default('feature'); // feature, bugfix, enhancement, breaking
            $table->date('released_at'); // วันที่ปล่อยเวอร์ชัน
            $table->boolean('is_major')->default(false); // เป็น major update หรือไม่
            $table->timestamps();

            $table->unique(['vendor_feature_id', 'version']);
            $table->index('released_at');
        });

        // ตาราง vendor_feature_purchases - การซื้อฟีเจอร์ของร้านค้า
        Schema::create('vendor_feature_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_feature_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_number')->unique(); // เลขที่การซื้อ (FP-YYYYMMDD-XXXXX)
            $table->decimal('price_paid', 10, 2); // ราคาที่จ่ายจริง
            $table->string('payment_method'); // wallet, stripe, promptpay
            $table->string('payment_status')->default('pending'); // pending, completed, failed, refunded
            $table->string('transaction_id')->nullable(); // ID จาก payment gateway
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchased_version'); // เวอร์ชันที่ซื้อ
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->timestamp('activated_at')->nullable(); // วันที่เปิดใช้งาน
            $table->timestamp('expires_at')->nullable(); // วันหมดอายุ (สำหรับแบบรายเดือน/ปี)
            $table->text('notes')->nullable(); // หมายเหตุ
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index(['payment_status', 'created_at']);
            $table->index('expires_at');
        });

        // ตาราง vendor_feature_changelogs - บันทึกการพัฒนาและการเปลี่ยนแปลง
        Schema::create('vendor_feature_changelogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_feature_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // added, changed, deprecated, removed, fixed, security
            $table->string('title'); // หัวข้อการเปลี่ยนแปลง
            $table->text('description'); // รายละเอียด
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->json('affected_areas')->nullable(); // ส่วนที่ได้รับผลกระทบ
            $table->timestamp('published_at')->nullable(); // วันที่เผยแพร่
            $table->boolean('is_visible')->default(true); // แสดง/ซ่อน
            $table->timestamps();

            $table->index(['vendor_feature_id', 'published_at']);
            $table->index(['type', 'priority']);
        });

        // ตาราง vendor_feature_usage_logs - บันทึกการใช้งานฟีเจอร์
        Schema::create('vendor_feature_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_feature_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // activated, deactivated, used, configured
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['vendor_feature_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_feature_usage_logs');
        Schema::dropIfExists('vendor_feature_changelogs');
        Schema::dropIfExists('vendor_feature_purchases');
        Schema::dropIfExists('vendor_feature_versions');
        Schema::dropIfExists('vendor_features');
    }
};

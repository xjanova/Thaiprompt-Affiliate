<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง software_licenses
     *
     * เก็บข้อมูล License Keys สำหรับซอฟต์แวร์ที่ซื้อ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('software_licenses')) {
            return;
        }

        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('software_product_id')
                ->constrained('software_products')
                ->onDelete('cascade')
                ->comment('สินค้าซอฟต์แวร์');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ซื้อ');

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null')
                ->comment('คำสั่งซื้อ');

            // License Key
            $table->string('license_key', 64)->unique()->comment('License Key (UUID/Random)');
            $table->string('activation_code', 32)->nullable()->comment('Activation Code (สำหรับ activate)');

            // Status
            $table->enum('status', ['active', 'inactive', 'expired', 'revoked'])
                ->default('active')
                ->comment('สถานะ License');

            // Activation
            $table->boolean('is_activated')->default(false)->comment('เปิดใช้งานแล้ว');
            $table->timestamp('activated_at')->nullable()->comment('วันที่เปิดใช้งาน');
            $table->string('activated_device')->nullable()->comment('อุปกรณ์ที่เปิดใช้งาน');
            $table->ipAddress('activated_ip')->nullable()->comment('IP ที่เปิดใช้งาน');

            // Expiration
            $table->timestamp('expires_at')->nullable()->comment('วันหมดอายุ (null = ไม่มีวันหมดอายุ)');
            $table->integer('max_activations')->default(1)->comment('จำนวนครั้งที่สามารถเปิดใช้งานได้');
            $table->integer('activation_count')->default(0)->comment('จำนวนครั้งที่เปิดใช้งานแล้ว');

            // Download Permissions
            $table->integer('max_downloads')->default(-1)->comment('จำนวนครั้งดาวน์โหลดสูงสุด (-1 = ไม่จำกัด)');
            $table->integer('download_count')->default(0)->comment('จำนวนครั้งที่ดาวน์โหลดแล้ว');
            $table->timestamp('last_downloaded_at')->nullable()->comment('ดาวน์โหลดครั้งล่าสุด');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม (JSON)');
            $table->text('notes')->nullable()->comment('หมายเหตุ');

            // Revocation
            $table->timestamp('revoked_at')->nullable()->comment('วันที่ถูกยกเลิก');
            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ผู้ยกเลิก');
            $table->text('revocation_reason')->nullable()->comment('เหตุผลที่ยกเลิก');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('license_key', 'lic_key_idx');
            $table->index('status', 'lic_status_idx');
            $table->index(['user_id', 'software_product_id'], 'lic_user_product_idx');
            $table->index('expires_at', 'lic_expires_idx');
        });
    }

    /**
     * ลบตาราง software_licenses
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};

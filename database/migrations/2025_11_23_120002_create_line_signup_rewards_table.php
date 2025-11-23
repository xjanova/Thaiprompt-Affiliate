<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง line_signup_rewards
     *
     * ตารางสำหรับกำหนดรางวัลที่จะให้เมื่อสมัครสมาชิก
     * รองรับรางวัลหลายประเภท: แต้ม, TPIX, คูปอง, สิทธิพิเศษ, ฯลฯ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('line_signup_rewards')) {
            return;
        }

        Schema::create('line_signup_rewards', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อรางวัล');
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->enum('signup_type', ['free', 'package', 'both'])
                ->default('both')
                ->comment('ประเภทการสมัคร: free=ฟรี, package=แพคเกจ, both=ทั้งสอง');

            // เงื่อนไขแพคเกจ (ถ้าเป็น signup_type = package)
            $table->json('package_ids')->nullable()->comment('ID ของแพคเกจที่สามารถรับรางวัลได้ (array)');

            // ประเภทรางวัล
            $table->enum('reward_type', [
                'wallet_points',      // แต้ม Wallet
                'tpix_tokens',        // เหรียญ TPIX
                'coupon',             // คูปองส่วนลด
                'rank_points',        // คะแนน Rank
                'special_benefit',    // สิทธิพิเศษ
                'bonus_item',         // ของแถม
                'free_downlines',     // ลูกทีมฟรี
                'experience_points',  // คะแนนประสบการณ์
            ])->comment('ประเภทของรางวัล');

            // ค่ารางวัล (แตกต่างตามประเภท)
            $table->decimal('amount', 20, 2)->default(0)->comment('จำนวน (สำหรับ points, tokens)');
            $table->string('coupon_code')->nullable()->comment('รหัสคูปอง (สำหรับ coupon)');
            $table->foreignId('coupon_template_id')->nullable()
                ->constrained('coupon_templates')->onDelete('set null')
                ->comment('Template คูปองที่จะสร้างให้ (auto-generate)');
            $table->json('benefit_data')->nullable()->comment('ข้อมูลสิทธิพิเศษ (JSON)');
            $table->foreignId('product_id')->nullable()
                ->constrained('products')->onDelete('set null')
                ->comment('สินค้าที่แถม (สำหรับ bonus_item)');

            // การแสดงผล
            $table->string('icon')->nullable()->comment('Icon รางวัล (FontAwesome class)');
            $table->string('badge_color')->default('#3B82F6')->comment('สีของ Badge');
            $table->integer('display_order')->default(0)->comment('ลำดับการแสดงผล');

            // เงื่อนไขเวลา
            $table->boolean('is_time_limited')->default(false)->comment('จำกัดช่วงเวลา?');
            $table->timestamp('start_date')->nullable()->comment('วันเริ่มต้นให้รางวัล');
            $table->timestamp('end_date')->nullable()->comment('วันสิ้นสุดให้รางวัล');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน?');
            $table->boolean('is_stackable')->default(true)->comment('สามารถรับได้หลายรางวัล?');

            // การแจ้งเตือน
            $table->boolean('notify_user')->default(true)->comment('แจ้งเตือนผู้ใช้?');
            $table->text('notification_message')->nullable()->comment('ข้อความแจ้งเตือน');

            // สถิติ
            $table->integer('total_claimed')->default(0)->comment('จำนวนครั้งที่มีคนรับรางวัล');
            $table->integer('max_claims')->nullable()->comment('จำนวนสูงสุดที่รับได้ (null = ไม่จำกัด)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('signup_type');
            $table->index('reward_type');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * ลบตาราง line_signup_rewards
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_rewards');
    }
};

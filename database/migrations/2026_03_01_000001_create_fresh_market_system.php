<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างระบบตลาดสดไทยพร้อม (Fresh Market Marketplace)
 *
 * ตาราง 9 ตาราง:
 * 1. fresh_market_settings - ตั้งค่าระบบ
 * 2. fresh_market_categories - หมวดหมู่สินค้า
 * 3. fresh_market_sellers - ผู้ขาย
 * 4. fresh_market_listings - รายการสินค้า
 * 5. fresh_market_orders - คำสั่งซื้อ
 * 6. fresh_market_conversations - ประวัติสนทนา AI
 * 7. fresh_market_messages - ข้อความในการสนทนา
 * 8. fresh_market_buyer_preferences - เงื่อนไขผู้ซื้อ
 * 9. fresh_market_referrals - ระบบแนะนำ/MLM
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตารางระบบตลาดสดไทยพร้อม
     */
    public function up(): void
    {
        // 1. ตั้งค่าระบบ
        $this->safeCreateTable('fresh_market_settings', function (Blueprint $table) {
            $table->id();

            // LINE OA Credentials (แยกจากดูดวง)
            $table->string('line_channel_id')->nullable();
            $table->string('line_channel_secret')->nullable();
            $table->text('line_channel_access_token')->nullable();

            // AI Settings
            $table->string('ai_provider', 50)->default('groq');
            $table->string('ai_model', 100)->default('llama-3.3-70b-versatile');
            $table->boolean('use_global_ai_settings')->default(true);
            $table->text('ai_system_prompt')->nullable()->comment('prompt สำหรับ พี่ตลาด');

            // ค่าบริการ
            $table->decimal('platform_fee_percentage', 5, 2)->default(3.00)->comment('% ต่อการขาย');
            $table->decimal('monthly_subscription_fee', 10, 2)->default(99.00)->comment('ค่าสมาชิกรายเดือน');
            $table->string('fee_mode', 20)->default('both')->comment('percentage|subscription|both');
            $table->integer('free_trial_days')->default(30)->comment('ทดลองใช้ฟรีกี่วัน');
            $table->integer('max_listings_free')->default(5)->comment('ลงขายฟรีได้กี่รายการ');
            $table->integer('max_listings_subscribed')->default(0)->comment('0 = unlimited');

            // ค้นหา
            $table->decimal('default_search_radius_km', 8, 2)->default(10.00);
            $table->decimal('max_search_radius_km', 8, 2)->default(50.00);

            // ตัวเลือก
            $table->boolean('escrow_enabled')->default(true);
            $table->boolean('cod_enabled')->default(true);
            $table->boolean('rider_enabled')->default(true);
            $table->boolean('mlm_commission_enabled')->default(true);
            $table->boolean('cashback_enabled')->default(true);

            // UI
            $table->string('line_flex_primary_color', 7)->default('#22C55E');
            $table->string('brand_name')->default('ตลาดสดไทยพร้อม');
            $table->text('welcome_message')->nullable();

            $table->timestamps();
        });

        // 2. หมวดหมู่สินค้า
        $this->safeCreateTable('fresh_market_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->nullable()->comment('emoji หรือ icon class');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('fresh_market_categories')
                ->onDelete('set null');

            $table->index('sort_order', 'fm_cat_sort_idx');
            $table->index('is_active', 'fm_cat_active_idx');
        });

        // 3. ผู้ขาย
        $this->safeCreateTable('fresh_market_sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('line_user_id', 100)->nullable();

            // ข้อมูลร้าน
            $table->string('shop_name');
            $table->text('shop_description')->nullable();
            $table->string('shop_image')->nullable();

            // พิกัด
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sub_district', 100)->nullable();

            // ติดต่อ
            $table->string('phone', 20)->nullable();
            $table->string('line_display_name')->nullable();

            // สมาชิก
            $table->string('subscription_type', 20)->default('free')->comment('free|monthly|yearly');
            $table->timestamp('subscription_expires_at')->nullable();

            // สถานะ
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspended')->default(false);

            // สถิติ
            $table->unsignedInteger('total_listings')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // MLM
            $table->unsignedBigInteger('mlm_member_id')->nullable();
            $table->string('referral_code', 20)->unique()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id', 'fm_seller_user_unique');
            $table->index('line_user_id', 'fm_seller_line_idx');
            $table->index(['latitude', 'longitude'], 'fm_seller_gps_idx');
            $table->index('is_active', 'fm_seller_active_idx');
            $table->index('subscription_type', 'fm_seller_sub_idx');

            $table->foreign('mlm_member_id')
                ->references('id')
                ->on('mlm_members')
                ->onDelete('set null');
        });

        // 4. รายการสินค้า
        $this->safeCreateTable('fresh_market_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('fresh_market_sellers')->onDelete('cascade');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('slug')->unique();

            // ข้อมูลสินค้า
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('quantity_available')->default(0);
            $table->string('unit', 30)->default('ชิ้น')->comment('กก., ถุง, กำ, ชิ้น, กล่อง');

            // รูปภาพ
            $table->json('images')->nullable()->comment('array ของ URL รูปภาพ');
            $table->string('main_image_url')->nullable();

            // พิกัด
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('delivery_radius_km', 8, 2)->default(10.00);

            // เวลาขาย
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();

            // คุณสมบัติ
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_organic')->default(false);
            $table->json('tags')->nullable();
            $table->string('freshness_level', 30)->default('สด')->comment('สด, สดมาก, ผลิตวันนี้');

            // MLM / Cashback
            $table->decimal('pv_value', 10, 2)->default(0)->comment('Point Value สำหรับ MLM');
            $table->decimal('cashback_amount', 10, 2)->default(0);
            $table->decimal('cashback_percentage', 5, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(3.00);

            // สถิติ
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('order_count')->default(0);

            // สถานะ
            $table->string('status', 20)->default('active')->comment('draft|active|sold_out|expired|suspended');
            $table->string('created_via', 10)->default('line')->comment('line|web');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                ->references('id')
                ->on('fresh_market_categories')
                ->onDelete('set null');

            $table->index(['latitude', 'longitude'], 'fm_listing_gps_idx');
            $table->index('status', 'fm_listing_status_idx');
            $table->index('category_id', 'fm_listing_cat_idx');
            $table->index('price', 'fm_listing_price_idx');
            $table->index('created_at', 'fm_listing_created_idx');
            $table->index('is_available', 'fm_listing_avail_idx');
        });

        // 5. คำสั่งซื้อ
        $this->safeCreateTable('fresh_market_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();

            // ผู้ซื้อ / ผู้ขาย
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('fresh_market_sellers')->onDelete('cascade');
            $table->unsignedBigInteger('listing_id')->nullable();

            // รายละเอียด
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('seller_earning', 10, 2)->default(0);

            // จัดส่ง
            $table->string('delivery_type', 20)->default('pickup')->comment('pickup|rider|shipping');
            $table->decimal('delivery_fee', 10, 2)->default(0);

            // ชำระเงิน
            $table->string('payment_method', 20)->default('cod')->comment('escrow|cod|transfer|wallet');
            $table->string('payment_status', 20)->default('pending')->comment('pending|paid|released|refunded');

            // สถานะออเดอร์
            $table->string('order_status', 20)->default('pending')
                ->comment('pending|accepted|preparing|ready|delivering|delivered|completed|cancelled');
            $table->string('escrow_status', 20)->nullable()->comment('held|released|refunded');

            // พิกัดผู้ซื้อ
            $table->decimal('buyer_latitude', 10, 8)->nullable();
            $table->decimal('buyer_longitude', 11, 8)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('delivery_notes')->nullable();

            // Rider
            $table->unsignedBigInteger('rider_job_id')->nullable();

            // Shipping
            $table->string('shipping_provider', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();

            // Timestamps สถานะ
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable()->comment('ผู้ซื้อยืนยันรับของ → ปล่อย escrow');

            // รีวิว
            $table->tinyInteger('buyer_rating')->nullable();
            $table->text('buyer_review')->nullable();
            $table->tinyInteger('seller_rating')->nullable();
            $table->text('seller_review')->nullable();

            // MLM / Cashback
            $table->decimal('cashback_amount', 10, 2)->default(0);
            $table->boolean('cashback_processed')->default(false);
            $table->boolean('mlm_commission_processed')->default(false);

            // ยกเลิก
            $table->text('cancel_reason')->nullable();
            $table->string('cancelled_by', 20)->nullable()->comment('buyer|seller|system');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('listing_id')
                ->references('id')
                ->on('fresh_market_listings')
                ->onDelete('set null');

            $table->foreign('rider_job_id')
                ->references('id')
                ->on('rider_jobs')
                ->onDelete('set null');

            $table->index('order_status', 'fm_order_status_idx');
            $table->index('payment_status', 'fm_order_pay_idx');
            $table->index('buyer_id', 'fm_order_buyer_idx');
            $table->index('created_at', 'fm_order_created_idx');
        });

        // 6. ประวัติสนทนา AI
        $this->safeCreateTable('fresh_market_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id', 100)->index('fm_conv_line_idx');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('role', 10)->default('buyer')->comment('buyer|seller');
            $table->string('conversation_state', 30)->default('new')
                ->comment('new|browsing|searching|listing|ordering|chatting');

            // บริบทการค้นหา
            $table->text('last_search_query')->nullable();
            $table->decimal('last_search_latitude', 10, 8)->nullable();
            $table->decimal('last_search_longitude', 11, 8)->nullable();
            $table->decimal('last_search_radius_km', 8, 2)->nullable();

            // ข้อมูลบริบท
            $table->json('preferences')->nullable()->comment('เงื่อนไขการหาสินค้า');
            $table->json('context')->nullable()->comment('บริบทการสนทนาปัจจุบัน');

            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // 7. ข้อความในการสนทนา
        $this->safeCreateTable('fresh_market_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('fresh_market_conversations')
                ->onDelete('cascade');

            $table->string('role', 15)->comment('user|assistant|system');
            $table->text('content');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม เช่น รูป, พิกัด');

            $table->unsignedInteger('tokens_used')->default(0);
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();

            $table->timestamps();

            $table->index('conversation_id', 'fm_msg_conv_idx');
        });

        // 8. เงื่อนไขผู้ซื้อ
        $this->safeCreateTable('fresh_market_buyer_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('line_user_id', 100)->nullable();

            $table->json('preferred_categories')->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('max_distance_km', 8, 2)->nullable();
            $table->boolean('preferred_organic')->default(false);
            $table->string('preferred_delivery_type', 20)->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('notification_enabled')->default(true);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('line_user_id', 'fm_pref_line_idx');
        });

        // 9. ระบบแนะนำ/MLM
        $this->safeCreateTable('fresh_market_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_user_id');
            $table->unsignedBigInteger('referrer_seller_id')->nullable();

            $table->string('referred_line_user_id', 100)->nullable();
            $table->unsignedBigInteger('referred_user_id')->nullable();

            $table->string('referral_token', 64)->unique();
            $table->string('status', 20)->default('pending')->comment('pending|followed|converted');
            $table->decimal('commission_earned', 10, 2)->default(0);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('referrer_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('referrer_seller_id')
                ->references('id')
                ->on('fresh_market_sellers')
                ->onDelete('set null');

            $table->foreign('referred_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('status', 'fm_ref_status_idx');
            $table->index('referred_line_user_id', 'fm_ref_line_idx');
        });
    }

    /**
     * ลบตารางระบบตลาดสดไทยพร้อม (ลบจากตารางลูกไปหาตารางแม่)
     */
    public function down(): void
    {
        $this->safeDropTable('fresh_market_referrals');
        $this->safeDropTable('fresh_market_buyer_preferences');
        $this->safeDropTable('fresh_market_messages');
        $this->safeDropTable('fresh_market_conversations');
        $this->safeDropTable('fresh_market_orders');
        $this->safeDropTable('fresh_market_listings');
        $this->safeDropTable('fresh_market_sellers');
        $this->safeDropTable('fresh_market_categories');
        $this->safeDropTable('fresh_market_settings');
    }
};

<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ร้านรถเข็น / ตลาดนัด (ร้านเคลื่อนที่) + ผู้ติดตามร้าน
 *
 * 1) fresh_market_sellers — สถานะหน้าร้านวันนี้
 *    - is_mobile              ร้านเคลื่อนที่ (ตำแหน่งร้าน = ตำแหน่งที่เปิดร้านวันนี้ ไม่ใช่ที่อยู่ที่ลงทะเบียน)
 *    - current_latitude/longitude, location_label, location_updated_at  ตำแหน่งปัจจุบัน (ร้านเคลื่อนที่)
 *    - is_open, opened_at, closes_at  เปิด/ปิดร้าน (ร้านเดิมทั้งหมด = เปิดอยู่ ตามพฤติกรรมเดิม)
 *    - live_location_sharing  ส่งตำแหน่งสดจากเครื่องร้านทุก ~30 วินาทีระหว่างเปิดร้าน
 * 2) fresh_market_shop_followers — ผู้ซื้อติดตามร้าน → แจ้งเตือนเมื่อร้านเปิด (สูงสุดครั้งละ 3 ชม. ต่อร้านต่อคน)
 *
 * ตั้งชื่อ 105000 ให้รันก่อน seed เมนูเปิดตัว (110100) — seed จะได้ตั้งร้านแอดมินเป็นร้านเคลื่อนที่ที่ยังปิดอยู่
 * prod ตอนนี้ fresh_market_* = 0 แถว → เพิ่มคอลัมน์ได้ปลอดภัย / รันซ้ำได้ (เช็คคอลัมน์/ตารางก่อนทุกครั้ง)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (Schema::hasTable('fresh_market_sellers')) {
            Schema::table('fresh_market_sellers', function (Blueprint $table) {
                $this->safeAddColumn($table, 'fresh_market_sellers', 'is_mobile', function ($table) {
                    $table->boolean('is_mobile')->default(false)
                        ->comment('ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'current_latitude', function ($table) {
                    $table->decimal('current_latitude', 10, 8)->nullable()
                        ->comment('ตำแหน่งปัจจุบันของร้านเคลื่อนที่');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'current_longitude', function ($table) {
                    $table->decimal('current_longitude', 11, 8)->nullable()
                        ->comment('ตำแหน่งปัจจุบันของร้านเคลื่อนที่');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'location_label', function ($table) {
                    $table->string('location_label', 150)->nullable()
                        ->comment('ชื่อจุดที่เปิดร้านวันนี้ เช่น ตลาดนัดหน้าโรงเรียน');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'location_updated_at', function ($table) {
                    $table->timestamp('location_updated_at')->nullable()
                        ->comment('เวลาที่ได้รับตำแหน่งปัจจุบันล่าสุด');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'is_open', function ($table) {
                    $table->boolean('is_open')->default(true)
                        ->comment('เปิดรับออเดอร์อยู่ (ปิด = ผู้ซื้อยังเห็นร้านแต่สั่งไม่ได้)');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'opened_at', function ($table) {
                    $table->timestamp('opened_at')->nullable()
                        ->comment('เวลาที่เปิดร้านรอบล่าสุด');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'closes_at', function ($table) {
                    $table->timestamp('closes_at')->nullable()
                        ->comment('ปิดร้านอัตโนมัติเมื่อถึงเวลานี้ (null = ไม่กำหนด)');
                });
                $this->safeAddColumn($table, 'fresh_market_sellers', 'live_location_sharing', function ($table) {
                    $table->boolean('live_location_sharing')->default(false)
                        ->comment('ร้านส่งตำแหน่งสดระหว่างเปิดร้าน');
                });
            });

            $this->safeAddIndex('fresh_market_sellers', ['is_open', 'closes_at'], 'fm_seller_open_idx');
        }

        if (! Schema::hasTable('fresh_market_shop_followers')) {
            Schema::create('fresh_market_shop_followers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('fresh_market_sellers')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('notify')->default(true)->comment('รับแจ้งเตือนเมื่อร้านเปิด');
                $table->timestamp('last_notified_at')->nullable()->comment('แจ้งเตือนร้านเปิดครั้งล่าสุด (กันแจ้งถี่)');
                $table->timestamps();

                $table->unique(['seller_id', 'user_id'], 'fm_follow_unique');
                $table->index('user_id', 'fm_follow_user_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fresh_market_shop_followers');

        if (Schema::hasTable('fresh_market_sellers')) {
            $this->safeDropIndex('fresh_market_sellers', 'fm_seller_open_idx');
            $this->safeDropColumn('fresh_market_sellers', [
                'is_mobile', 'current_latitude', 'current_longitude', 'location_label', 'location_updated_at',
                'is_open', 'opened_at', 'closes_at', 'live_location_sharing',
            ]);
        }
    }
};

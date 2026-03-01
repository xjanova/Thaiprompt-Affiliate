<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ phone_verified_at ใน fresh_market_sellers
 *
 * สำหรับ OTP verification ก่อนเริ่มลงขายสินค้า
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fresh_market_sellers', function (Blueprint $table) {
            if (! Schema::hasColumn('fresh_market_sellers', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone')
                    ->comment('เวลาที่ยืนยันเบอร์โทรผ่าน OTP');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fresh_market_sellers', function (Blueprint $table) {
            if (Schema::hasColumn('fresh_market_sellers', 'phone_verified_at')) {
                $table->dropColumn('phone_verified_at');
            }
        });
    }
};

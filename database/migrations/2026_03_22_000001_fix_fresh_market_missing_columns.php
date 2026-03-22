<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขคอลัมน์ที่ขาดหายในระบบตลาดสด
 *
 * 1. fresh_market_conversations: เพิ่ม seller_id, state_updated_at, state_step, state_total_steps, previous_state
 * 2. fresh_market_sellers: เพิ่ม phone_verified_at
 * 3. rider_jobs: เพิ่ม fresh_market ใน job_type (เปลี่ยนจาก enum เป็น string), เพิ่ม buyer_line_user_id
 * 4. fresh_market_orders: เพิ่ม FK constraint rider_id
 * 5. service_providers: เพิ่ม FK constraint rider_id
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ที่ขาดหาย
     */
    public function up(): void
    {
        // === 1. fresh_market_conversations: เพิ่มคอลัมน์สำหรับ state machine ===
        Schema::table('fresh_market_conversations', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fresh_market_conversations', 'seller_id', function ($table) {
                $table->unsignedBigInteger('seller_id')->nullable()->after('user_id')
                    ->comment('FK ไปหา fresh_market_sellers');
            });

            $this->safeAddColumn($table, 'fresh_market_conversations', 'state_updated_at', function ($table) {
                $table->timestamp('state_updated_at')->nullable()->after('conversation_state')
                    ->comment('เวลาที่เปลี่ยน state ล่าสุด');
            });

            $this->safeAddColumn($table, 'fresh_market_conversations', 'state_step', function ($table) {
                $table->unsignedTinyInteger('state_step')->default(0)->after('state_updated_at')
                    ->comment('ขั้นตอนปัจจุบันใน flow');
            });

            $this->safeAddColumn($table, 'fresh_market_conversations', 'state_total_steps', function ($table) {
                $table->unsignedTinyInteger('state_total_steps')->default(0)->after('state_step')
                    ->comment('จำนวนขั้นตอนทั้งหมดใน flow');
            });

            $this->safeAddColumn($table, 'fresh_market_conversations', 'previous_state', function ($table) {
                $table->string('previous_state', 30)->nullable()->after('state_total_steps')
                    ->comment('state ก่อนหน้า (สำหรับ back/undo)');
            });
        });

        // เพิ่ม FK constraint seller_id (แยกจาก closure ด้านบนเพื่อความปลอดภัย)
        if (Schema::hasColumn('fresh_market_conversations', 'seller_id')) {
            try {
                Schema::table('fresh_market_conversations', function (Blueprint $table) {
                    $table->foreign('seller_id', 'fm_conv_seller_fk')
                        ->references('id')
                        ->on('fresh_market_sellers')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // FK อาจมีอยู่แล้ว — ข้าม
            }
        }

        // === 2. fresh_market_sellers: เพิ่ม phone_verified_at ===
        Schema::table('fresh_market_sellers', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fresh_market_sellers', 'phone_verified_at', function ($table) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone')
                    ->comment('เวลาที่ยืนยันเบอร์โทรผ่าน OTP');
            });
        });

        // === 3. rider_jobs: เปลี่ยน job_type จาก enum เป็น string เพื่อรองรับ fresh_market ===
        if (Schema::hasColumn('rider_jobs', 'job_type')) {
            try {
                // เปลี่ยนจาก enum เป็น string เพื่อรองรับ fresh_market
                Schema::table('rider_jobs', function (Blueprint $table) {
                    $table->string('job_type', 30)->default('delivery')->change();
                });
            } catch (\Exception $e) {
                // อาจเป็น string อยู่แล้ว — ข้าม
            }
        }

        // เพิ่ม buyer_line_user_id (ใช้สำหรับแจ้งเตือนผู้ซื้อผ่าน LINE)
        Schema::table('rider_jobs', function (Blueprint $table) {
            $this->safeAddColumn($table, 'rider_jobs', 'buyer_line_user_id', function ($table) {
                $table->string('buyer_line_user_id', 100)->nullable()->after('customer_id')
                    ->comment('LINE user ID ของผู้ซื้อ (สำหรับแจ้งเตือน)');
            });
        });

        // === 4. fresh_market_orders: เพิ่ม rider_id FK ===
        if (Schema::hasColumn('fresh_market_orders', 'rider_id')) {
            try {
                Schema::table('fresh_market_orders', function (Blueprint $table) {
                    $table->foreign('rider_id', 'fm_order_rider_fk')
                        ->references('id')
                        ->on('riders')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // FK อาจมีอยู่แล้ว — ข้าม
            }
        }

        // === 5. service_providers: เพิ่ม rider_id FK ===
        if (Schema::hasColumn('service_providers', 'rider_id')) {
            try {
                Schema::table('service_providers', function (Blueprint $table) {
                    $table->foreign('rider_id', 'sp_rider_fk')
                        ->references('id')
                        ->on('riders')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // FK อาจมีอยู่แล้ว — ข้าม
            }
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        // Drop FK ก่อนลบคอลัมน์
        try {
            Schema::table('fresh_market_conversations', function (Blueprint $table) {
                $table->dropForeign('fm_conv_seller_fk');
            });
        } catch (\Exception $e) {
            //
        }

        $this->safeDropColumn('fresh_market_conversations', [
            'seller_id', 'state_updated_at', 'state_step', 'state_total_steps', 'previous_state',
        ]);
        $this->safeDropColumn('fresh_market_sellers', ['phone_verified_at']);
        $this->safeDropColumn('rider_jobs', ['buyer_line_user_id']);

        try {
            Schema::table('fresh_market_orders', function (Blueprint $table) {
                $table->dropForeign('fm_order_rider_fk');
            });
        } catch (\Exception $e) {
            //
        }

        try {
            Schema::table('service_providers', function (Blueprint $table) {
                $table->dropForeign('sp_rider_fk');
            });
        } catch (\Exception $e) {
            //
        }
    }
};

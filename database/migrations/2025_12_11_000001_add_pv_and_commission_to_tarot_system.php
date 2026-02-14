<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบ PV และ Commission สำหรับการทำนายไพ่ทาโร่ต์
     *
     * Platform Fee = ค่าบริการของระบบ (แอดมินตั้ง)
     * PV Percentage = ค่าการตลาด/คอมมิชชั่น MLM
     */
    public function up(): void
    {
        // 1. เพิ่ม fields ใน tarot_reading_categories
        if (Schema::hasTable('tarot_reading_categories')) {
            Schema::table('tarot_reading_categories', function (Blueprint $table) {
                // ค่าบริการแพลตฟอร์ม (%)
                if (! Schema::hasColumn('tarot_reading_categories', 'platform_fee_percentage')) {
                    $table->decimal('platform_fee_percentage', 5, 2)
                        ->default(10.00)
                        ->after('price')
                        ->comment('ค่าบริการแพลตฟอร์ม (%) - แอดมินกำหนด');
                }

                // ค่า PV สำหรับ MLM (%)
                if (! Schema::hasColumn('tarot_reading_categories', 'pv_percentage')) {
                    $table->decimal('pv_percentage', 5, 2)
                        ->default(10.00)
                        ->after('platform_fee_percentage')
                        ->comment('ค่า PV/คอมมิชชั่น MLM (%)');
                }

                // เปิด/ปิดการแบ่งคอมมิชชั่น
                if (! Schema::hasColumn('tarot_reading_categories', 'commission_enabled')) {
                    $table->boolean('commission_enabled')
                        ->default(true)
                        ->after('pv_percentage')
                        ->comment('เปิด/ปิดการแบ่งคอมมิชชั่น MLM');
                }
            });
        }

        // 2. เพิ่ม fields ใน tarot_readings (บันทึกค่าจริงที่เกิดขึ้น)
        if (Schema::hasTable('tarot_readings')) {
            Schema::table('tarot_readings', function (Blueprint $table) {
                // ค่าบริการแพลตฟอร์มที่หักไป
                if (! Schema::hasColumn('tarot_readings', 'platform_fee')) {
                    $table->decimal('platform_fee', 10, 2)
                        ->default(0)
                        ->after('amount_paid')
                        ->comment('ค่าบริการแพลตฟอร์มที่หักไป');
                }

                // PV ที่คำนวณได้
                if (! Schema::hasColumn('tarot_readings', 'pv_amount')) {
                    $table->decimal('pv_amount', 10, 2)
                        ->default(0)
                        ->after('platform_fee')
                        ->comment('PV ที่คำนวณได้');
                }

                // คอมมิชชั่นรวมที่แบ่งออกไป
                if (! Schema::hasColumn('tarot_readings', 'total_commission')) {
                    $table->decimal('total_commission', 10, 2)
                        ->default(0)
                        ->after('pv_amount')
                        ->comment('คอมมิชชั่นรวมที่แบ่งออกไป');
                }

                // สถานะการจ่ายคอมมิชชั่น
                if (! Schema::hasColumn('tarot_readings', 'commission_status')) {
                    $table->enum('commission_status', ['pending', 'processed', 'failed'])
                        ->default('pending')
                        ->after('total_commission')
                        ->comment('สถานะการจ่ายคอมมิชชั่น');
                }

                // Payment method ที่ใช้
                if (! Schema::hasColumn('tarot_readings', 'payment_method')) {
                    $table->string('payment_method', 50)
                        ->nullable()
                        ->after('commission_status')
                        ->comment('วิธีการชำระเงิน');
                }

                // Payment transaction ID
                if (! Schema::hasColumn('tarot_readings', 'payment_transaction_id')) {
                    $table->unsignedBigInteger('payment_transaction_id')
                        ->nullable()
                        ->after('payment_method')
                        ->comment('ID ของ payment transaction');
                }
            });
        }

        // 3. สร้างตาราง tarot_commissions สำหรับเก็บประวัติคอมมิชชั่น
        if (! Schema::hasTable('tarot_commissions')) {
            Schema::create('tarot_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tarot_reading_id')->comment('ID ของการทำนาย');
                $table->unsignedBigInteger('user_id')->comment('ID ผู้รับคอมมิชชั่น');
                $table->unsignedBigInteger('mlm_member_id')->nullable()->comment('ID ของ MLM member');
                $table->integer('level')->default(1)->comment('ระดับ MLM (1=direct, 2=upline level 2, ...)');
                $table->decimal('commission_rate', 5, 2)->default(0)->comment('อัตราคอมมิชชั่น (%)');
                $table->decimal('commission_amount', 10, 2)->default(0)->comment('จำนวนคอมมิชชั่น');
                $table->decimal('pv_amount', 10, 2)->default(0)->comment('PV ที่ได้รับ');
                $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('tarot_reading_id');
                $table->index('user_id');
                $table->index('mlm_member_id');
                $table->index('status');
                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * ลบคอลัมน์และตารางที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        // ลบตาราง tarot_commissions
        Schema::dropIfExists('tarot_commissions');

        // ลบ fields จาก tarot_readings
        if (Schema::hasTable('tarot_readings')) {
            Schema::table('tarot_readings', function (Blueprint $table) {
                $columns = ['platform_fee', 'pv_amount', 'total_commission', 'commission_status', 'payment_method', 'payment_transaction_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tarot_readings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // ลบ fields จาก tarot_reading_categories
        if (Schema::hasTable('tarot_reading_categories')) {
            Schema::table('tarot_reading_categories', function (Blueprint $table) {
                $columns = ['platform_fee_percentage', 'pv_percentage', 'commission_enabled'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tarot_reading_categories', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

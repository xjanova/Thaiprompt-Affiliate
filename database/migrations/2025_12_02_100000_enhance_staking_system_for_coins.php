<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับเพิ่มความสามารถใช้ Coin ในระบบ Staking
 *
 * เพิ่มคอลัมน์:
 * - investment_plans: รองรับการลงทุนด้วย Coin, อัตราแลกเปลี่ยน, สถานะเปิด/ปิดแผน
 * - staking_positions: บันทึกการลงทุนด้วย Coin
 * - สร้างตาราง staking_coin_settings: ตั้งค่าอัตราแลกเปลี่ยน Coin -> THB
 *
 * @version 3.296.0
 *
 * @since 2025-12-02
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. เพิ่มคอลัมน์ใน investment_plans
        Schema::table('investment_plans', function (Blueprint $table) {
            // รองรับการลงทุนด้วย Coin
            if (! Schema::hasColumn('investment_plans', 'allow_coin_investment')) {
                $table->boolean('allow_coin_investment')->default(false)
                    ->after('is_featured')
                    ->comment('อนุญาตให้ลงทุนด้วย Coin');
            }

            if (! Schema::hasColumn('investment_plans', 'coin_to_money_rate')) {
                $table->decimal('coin_to_money_rate', 10, 4)->default(1.0000)
                    ->after('allow_coin_investment')
                    ->comment('อัตราแลกเปลี่ยน: 1 Coin = X บาท');
            }

            if (! Schema::hasColumn('investment_plans', 'min_coin_amount')) {
                $table->decimal('min_coin_amount', 20, 2)->nullable()
                    ->after('coin_to_money_rate')
                    ->comment('จำนวน Coin ขั้นต่ำในการลงทุน');
            }

            if (! Schema::hasColumn('investment_plans', 'max_coin_amount')) {
                $table->decimal('max_coin_amount', 20, 2)->nullable()
                    ->after('min_coin_amount')
                    ->comment('จำนวน Coin สูงสุดในการลงทุน');
            }

            // สถานะการเปิด/ปิดแผน (Maintenance Mode)
            if (! Schema::hasColumn('investment_plans', 'is_paused')) {
                $table->boolean('is_paused')->default(false)
                    ->after('is_active')
                    ->comment('หยุดรับการลงทุนชั่วคราว');
            }

            if (! Schema::hasColumn('investment_plans', 'pause_reason')) {
                $table->string('pause_reason')->nullable()
                    ->after('is_paused')
                    ->comment('เหตุผลที่หยุดรับการลงทุน');
            }

            // ข้อความเตือนความเสี่ยง
            if (! Schema::hasColumn('investment_plans', 'risk_warning')) {
                $table->text('risk_warning')->nullable()
                    ->after('terms_conditions')
                    ->comment('ข้อความเตือนความเสี่ยง');
            }

            if (! Schema::hasColumn('investment_plans', 'risk_level')) {
                $table->enum('risk_level', ['low', 'medium', 'high', 'very_high'])->default('medium')
                    ->after('risk_warning')
                    ->comment('ระดับความเสี่ยง');
            }

            // เพิ่มจำนวน pot ที่รองรับ
            if (! Schema::hasColumn('investment_plans', 'max_positions_per_user')) {
                $table->integer('max_positions_per_user')->nullable()
                    ->after('max_investors')
                    ->comment('จำนวน pot สูงสุดต่อ user (null = ไม่จำกัด)');
            }

            // ช่วงเวลาที่เปิดรับการลงทุน
            if (! Schema::hasColumn('investment_plans', 'open_at')) {
                $table->timestamp('open_at')->nullable()
                    ->after('sort_order')
                    ->comment('เปิดรับการลงทุนตั้งแต่');
            }

            if (! Schema::hasColumn('investment_plans', 'close_at')) {
                $table->timestamp('close_at')->nullable()
                    ->after('open_at')
                    ->comment('ปิดรับการลงทุนเมื่อ');
            }
        });

        // 2. เพิ่มคอลัมน์ใน staking_positions
        Schema::table('staking_positions', function (Blueprint $table) {
            // บันทึกการลงทุนด้วย Coin
            if (! Schema::hasColumn('staking_positions', 'coin_amount')) {
                $table->decimal('coin_amount', 20, 2)->nullable()
                    ->after('amount')
                    ->comment('จำนวน Coin ที่ใช้ลงทุน (ถ้าลงทุนด้วย Coin)');
            }

            if (! Schema::hasColumn('staking_positions', 'coin_rate_used')) {
                $table->decimal('coin_rate_used', 10, 4)->nullable()
                    ->after('coin_amount')
                    ->comment('อัตราแลกเปลี่ยนที่ใช้ตอนลงทุน');
            }

            if (! Schema::hasColumn('staking_positions', 'payment_method')) {
                $table->enum('payment_method', ['wallet', 'coin', 'mixed'])->default('wallet')
                    ->after('coin_rate_used')
                    ->comment('วิธีการชำระ: wallet=เงิน, coin=Coin, mixed=ผสม');
            }

            // ติดตาม ROI distribution
            if (! Schema::hasColumn('staking_positions', 'next_roi_at')) {
                $table->timestamp('next_roi_at')->nullable()
                    ->after('last_roi_distribution_at')
                    ->comment('กำหนดการจ่าย ROI ครั้งถัดไป');
            }

            if (! Schema::hasColumn('staking_positions', 'total_distributions')) {
                $table->integer('total_distributions')->default(0)
                    ->after('next_roi_at')
                    ->comment('จำนวนครั้งที่จ่าย ROI ไปแล้ว');
            }

            if (! Schema::hasColumn('staking_positions', 'remaining_distributions')) {
                $table->integer('remaining_distributions')->nullable()
                    ->after('total_distributions')
                    ->comment('จำนวนครั้งที่เหลือที่จะจ่าย ROI');
            }
        });

        // 3. สร้างตาราง staking_coin_settings
        if (! Schema::hasTable('staking_coin_settings')) {
            Schema::create('staking_coin_settings', function (Blueprint $table) {
                $table->id();

                // อัตราแลกเปลี่ยน Coin -> THB
                $table->decimal('coin_to_thb_rate', 10, 4)->default(1.0000)
                    ->comment('อัตราแลกเปลี่ยน: 1 Coin = X บาท');

                // การใช้ Coin ในการลงทุน
                $table->boolean('allow_coin_staking')->default(true)
                    ->comment('เปิดใช้งานการลงทุนด้วย Coin');

                $table->boolean('allow_mixed_payment')->default(true)
                    ->comment('อนุญาตให้ใช้ Coin + เงินรวมกัน');

                $table->decimal('min_coin_percentage', 5, 2)->default(0)
                    ->comment('สัดส่วน Coin ขั้นต่ำ (%)');

                $table->decimal('max_coin_percentage', 5, 2)->default(100)
                    ->comment('สัดส่วน Coin สูงสุด (%)');

                // ระบบความเสี่ยง
                $table->boolean('require_risk_acknowledgment')->default(true)
                    ->comment('บังคับให้ยอมรับความเสี่ยงก่อนลงทุน');

                $table->text('default_risk_warning')->nullable()
                    ->comment('ข้อความเตือนความเสี่ยงเริ่มต้น');

                // ตั้งค่าทั่วไป
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                // Index
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบคอลัมน์จาก investment_plans
        Schema::table('investment_plans', function (Blueprint $table) {
            $columns = [
                'allow_coin_investment', 'coin_to_money_rate', 'min_coin_amount', 'max_coin_amount',
                'is_paused', 'pause_reason', 'risk_warning', 'risk_level',
                'max_positions_per_user', 'open_at', 'close_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('investment_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // ลบคอลัมน์จาก staking_positions
        Schema::table('staking_positions', function (Blueprint $table) {
            $columns = [
                'coin_amount', 'coin_rate_used', 'payment_method',
                'next_roi_at', 'total_distributions', 'remaining_distributions',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('staking_positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // ลบตาราง
        Schema::dropIfExists('staking_coin_settings');
    }
};

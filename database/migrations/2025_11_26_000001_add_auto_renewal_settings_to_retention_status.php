<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ Auto-Renewal Settings ในตาราง membership_retention_status
     *
     * ระบบรักษายอดอัตโนมัติแบบเทพ - ผู้ใช้ตั้งค่าเองได้
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง membership_retention_status มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('membership_retention_status')) {
            return;
        }

        Schema::table('membership_retention_status', function (Blueprint $table) {
            // เช็คและเพิ่มคอลัมน์ auto-renewal
            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_enabled')) {
                $table->boolean('auto_renewal_enabled')->default(false)->after('last_active_date')
                    ->comment('เปิด/ปิด การรักษายอดอัตโนมัติ');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_source')) {
                $table->enum('auto_renewal_source', ['wallet', 'commission', 'both'])->default('wallet')->after('auto_renewal_enabled')
                    ->comment('แหล่งเงินสำหรับรักษายอดอัตโนมัติ: wallet=กระเป๋าเงิน, commission=คอมมิชชั่น, both=ทั้งสองแหล่ง');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_priority')) {
                $table->enum('auto_renewal_priority', ['wallet_first', 'commission_first'])->default('wallet_first')->after('auto_renewal_source')
                    ->comment('ลำดับการหักเงิน: wallet_first=หักจาก wallet ก่อน, commission_first=หักจาก commission ก่อน');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_max_amount')) {
                $table->decimal('auto_renewal_max_amount', 10, 2)->nullable()->after('auto_renewal_priority')
                    ->comment('จำนวนเงินสูงสุดที่อนุญาตให้หักต่อครั้ง (null = ไม่จำกัด)');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_months')) {
                $table->integer('auto_renewal_months')->default(1)->after('auto_renewal_max_amount')
                    ->comment('จำนวนเดือนที่จะต่ออายุอัตโนมัติ (1-12)');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_notify_before_days')) {
                $table->integer('auto_renewal_notify_before_days')->default(3)->after('auto_renewal_months')
                    ->comment('แจ้งเตือนกี่วันก่อนหักเงินอัตโนมัติ');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_last_executed')) {
                $table->timestamp('auto_renewal_last_executed')->nullable()->after('auto_renewal_notify_before_days')
                    ->comment('วันที่ทำ auto-renewal ครั้งล่าสุด');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_failed_count')) {
                $table->integer('auto_renewal_failed_count')->default(0)->after('auto_renewal_last_executed')
                    ->comment('จำนวนครั้งที่ auto-renewal ล้มเหลว');
            }

            if (!Schema::hasColumn('membership_retention_status', 'auto_renewal_paused_until')) {
                $table->date('auto_renewal_paused_until')->nullable()->after('auto_renewal_failed_count')
                    ->comment('หยุด auto-renewal ชั่วคราวจนถึงวันที่');
            }
        });
    }

    /**
     * ลบคอลัมน์ Auto-Renewal Settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('membership_retention_status', function (Blueprint $table) {
            $columns = [
                'auto_renewal_enabled',
                'auto_renewal_source',
                'auto_renewal_priority',
                'auto_renewal_max_amount',
                'auto_renewal_months',
                'auto_renewal_notify_before_days',
                'auto_renewal_last_executed',
                'auto_renewal_failed_count',
                'auto_renewal_paused_until',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('membership_retention_status', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

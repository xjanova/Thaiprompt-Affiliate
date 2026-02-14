<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์สำหรับเชื่อมต่อ SMS Payment กับ Fortune Reading
     *
     * - sms_notification_id: เชื่อม SMS notification ที่ตรวจจับยอดเงินพิเศษ
     * - sender_info: เก็บข้อมูลผู้โอน (สำหรับบิลลอย)
     * - is_floating: บิลลอย = ยังไม่ระบุตัวตนลูกค้า
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'sms_notification_id')) {
                $table->unsignedBigInteger('sms_notification_id')->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('fortune_readings', 'sender_info')) {
                $table->string('sender_info')->nullable()->after('sms_notification_id')
                    ->comment('ข้อมูลผู้โอน จาก SMS (ชื่อบัญชี/เลขอ้างอิง)');
            }

            if (! Schema::hasColumn('fortune_readings', 'sender_bank')) {
                $table->string('sender_bank')->nullable()->after('sender_info')
                    ->comment('ธนาคารที่โอนเข้า');
            }

            if (! Schema::hasColumn('fortune_readings', 'is_floating')) {
                $table->boolean('is_floating')->default(false)->after('sender_bank')
                    ->comment('บิลลอย = ยังไม่ระบุตัวตนลูกค้า');
            }
        });
    }

    /**
     * ลบฟิลด์ SMS Payment ออกจาก fortune_readings
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_readings', 'sms_notification_id')) {
                $table->dropColumn('sms_notification_id');
            }
            if (Schema::hasColumn('fortune_readings', 'sender_info')) {
                $table->dropColumn('sender_info');
            }
            if (Schema::hasColumn('fortune_readings', 'sender_bank')) {
                $table->dropColumn('sender_bank');
            }
            if (Schema::hasColumn('fortune_readings', 'is_floating')) {
                $table->dropColumn('is_floating');
            }
        });
    }
};

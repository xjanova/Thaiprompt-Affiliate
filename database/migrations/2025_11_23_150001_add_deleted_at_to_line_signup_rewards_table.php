<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม column deleted_at ในตาราง line_signup_rewards
     *
     * เพิ่ม SoftDelete support ถ้ายังไม่มี
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง line_signup_rewards มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('line_signup_rewards')) {
            return;
        }

        Schema::table('line_signup_rewards', function (Blueprint $table) {
            // เช็คว่ามี column deleted_at หรือยัง
            if (! Schema::hasColumn('line_signup_rewards', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * ลบ column deleted_at
     */
    public function down(): void
    {
        Schema::table('line_signup_rewards', function (Blueprint $table) {
            if (Schema::hasColumn('line_signup_rewards', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

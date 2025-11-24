<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์สำหรับการ Block/Unblock สินค้าและบริการโดย Admin
     *
     * ฟิลด์ที่เพิ่ม:
     * - is_blocked: สถานะการบล็อก (boolean)
     * - blocked_at: วันที่บล็อก (datetime)
     * - blocked_by: ผู้ที่บล็อก (user_id)
     * - block_reason: เหตุผลในการบล็อก (text)
     * - unblocked_at: วันที่ปลดบล็อก (datetime)
     * - unblocked_by: ผู้ที่ปลดบล็อก (user_id)
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่มฟิลด์ใน products table
        Schema::table('products', function (Blueprint $table) {
            // เช็คว่าคอลัมน์ยังไม่มีก่อนเพิ่ม
            if (!Schema::hasColumn('products', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('products', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            }

            if (!Schema::hasColumn('products', 'blocked_by')) {
                $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete()->after('blocked_at');
            }

            if (!Schema::hasColumn('products', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('blocked_by');
            }

            if (!Schema::hasColumn('products', 'unblocked_at')) {
                $table->timestamp('unblocked_at')->nullable()->after('block_reason');
            }

            if (!Schema::hasColumn('products', 'unblocked_by')) {
                $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete()->after('unblocked_at');
            }
        });

        // เพิ่มฟิลด์ใน services table
        Schema::table('services', function (Blueprint $table) {
            // เช็คว่าคอลัมน์ยังไม่มีก่อนเพิ่ม
            if (!Schema::hasColumn('services', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('services', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            }

            if (!Schema::hasColumn('services', 'blocked_by')) {
                $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete()->after('blocked_at');
            }

            if (!Schema::hasColumn('services', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('blocked_by');
            }

            if (!Schema::hasColumn('services', 'unblocked_at')) {
                $table->timestamp('unblocked_at')->nullable()->after('block_reason');
            }

            if (!Schema::hasColumn('services', 'unblocked_by')) {
                $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete()->after('unblocked_at');
            }
        });
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ลบ foreign keys ก่อน
            if (Schema::hasColumn('products', 'blocked_by')) {
                $table->dropForeign(['blocked_by']);
                $table->dropColumn('blocked_by');
            }
            if (Schema::hasColumn('products', 'unblocked_by')) {
                $table->dropForeign(['unblocked_by']);
                $table->dropColumn('unblocked_by');
            }

            // ลบคอลัมน์อื่นๆ
            $columns = ['is_blocked', 'blocked_at', 'block_reason', 'unblocked_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('services', function (Blueprint $table) {
            // ลบ foreign keys ก่อน
            if (Schema::hasColumn('services', 'blocked_by')) {
                $table->dropForeign(['blocked_by']);
                $table->dropColumn('blocked_by');
            }
            if (Schema::hasColumn('services', 'unblocked_by')) {
                $table->dropForeign(['unblocked_by']);
                $table->dropColumn('unblocked_by');
            }

            // ลบคอลัมน์อื่นๆ
            $columns = ['is_blocked', 'blocked_at', 'block_reason', 'unblocked_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

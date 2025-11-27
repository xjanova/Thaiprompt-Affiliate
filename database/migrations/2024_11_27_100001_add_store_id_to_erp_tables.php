<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม store_id ให้ตาราง ERP (departments, positions, employees)
 * เพื่อแยกข้อมูลตามร้านค้า - ใช้ฟรีทุกร้าน
 *
 * @author AI Assistant
 * @since 2024-11-27
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่ม store_id สำหรับแยกข้อมูลตามร้านค้า
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่ม store_id ให้ตาราง departments
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'store_id')) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vendor_stores')
                    ->onDelete('cascade');

                // Index สำหรับค้นหาตาม store
                $table->index('store_id', 'departments_store_id_index');
            }
        });

        // เพิ่ม store_id ให้ตาราง positions
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'store_id')) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vendor_stores')
                    ->onDelete('cascade');

                // Index สำหรับค้นหาตาม store
                $table->index('store_id', 'positions_store_id_index');
            }
        });

        // เพิ่ม store_id ให้ตาราง employees
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'store_id')) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vendor_stores')
                    ->onDelete('cascade');

                // Index สำหรับค้นหาตาม store
                $table->index('store_id', 'employees_store_id_index');
            }

            // ทำให้ user_id เป็น nullable (พนักงานไม่จำเป็นต้องมี account)
            // แก้ไขเฉพาะเมื่อยังไม่เป็น nullable
        });

        // เพิ่ม store_id ให้ตาราง attendance_records (ถ้ามี)
        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (!Schema::hasColumn('attendance_records', 'store_id')) {
                    $table->foreignId('store_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('vendor_stores')
                        ->onDelete('cascade');

                    $table->index('store_id', 'attendance_records_store_id_idx');
                }
            });
        }

        // เพิ่ม store_id ให้ตาราง leave_requests (ถ้ามี)
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_requests', 'store_id')) {
                    $table->foreignId('store_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('vendor_stores')
                        ->onDelete('cascade');

                    $table->index('store_id', 'leave_requests_store_id_index');
                }
            });
        }

        // เพิ่ม store_id ให้ตาราง work_shifts
        if (Schema::hasTable('work_shifts')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                if (!Schema::hasColumn('work_shifts', 'store_id')) {
                    $table->foreignId('store_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('vendor_stores')
                        ->onDelete('cascade');

                    $table->index('store_id', 'work_shifts_store_id_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     * ลบ store_id ออกจากตาราง ERP
     *
     * @return void
     */
    public function down(): void
    {
        // ลบจาก work_shifts
        if (Schema::hasTable('work_shifts') && Schema::hasColumn('work_shifts', 'store_id')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('work_shifts_store_id_index');
                $table->dropColumn('store_id');
            });
        }

        // ลบจาก leave_requests
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'store_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('leave_requests_store_id_index');
                $table->dropColumn('store_id');
            });
        }

        // ลบจาก attendance_records
        if (Schema::hasTable('attendance_records') && Schema::hasColumn('attendance_records', 'store_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('attendance_records_store_id_idx');
                $table->dropColumn('store_id');
            });
        }

        // ลบจาก employees
        if (Schema::hasColumn('employees', 'store_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('employees_store_id_index');
                $table->dropColumn('store_id');
            });
        }

        // ลบจาก positions
        if (Schema::hasColumn('positions', 'store_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('positions_store_id_index');
                $table->dropColumn('store_id');
            });
        }

        // ลบจาก departments
        if (Schema::hasColumn('departments', 'store_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropIndex('departments_store_id_index');
                $table->dropColumn('store_id');
            });
        }
    }
};

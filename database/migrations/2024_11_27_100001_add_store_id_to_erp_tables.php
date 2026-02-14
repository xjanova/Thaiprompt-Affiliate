<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม store_id ให้ตาราง ERP (departments, positions, employees)
 * เพื่อแยกข้อมูลตามร้านค้า - ใช้ฟรีทุกร้าน
 *
 * @author AI Assistant
 *
 * @since 2024-11-27
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่ม store_id สำหรับแยกข้อมูลตามร้านค้า
     *
     * ⚠️ vendor_stores ถูกสร้างใน migration 2025_11_03
     * ดังนั้นต้องเช็คว่า vendor_stores มีอยู่ก่อนเพิ่ม foreign key
     */
    public function up(): void
    {
        $vendorStoresExists = Schema::hasTable('vendor_stores');

        // เพิ่ม store_id ให้ตาราง departments
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (! Schema::hasColumn('departments', 'store_id')) {
                    // เพิ่มคอลัมน์ก่อน
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id', 'departments_store_id_index');
                }
            });

            // เพิ่ม foreign key ถ้า vendor_stores มีอยู่
            if ($vendorStoresExists && Schema::hasColumn('departments', 'store_id')) {
                try {
                    Schema::table('departments', function (Blueprint $table) {
                        $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว - ข้าม
                }
            }
        }

        // เพิ่ม store_id ให้ตาราง positions
        if (Schema::hasTable('positions')) {
            Schema::table('positions', function (Blueprint $table) {
                if (! Schema::hasColumn('positions', 'store_id')) {
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id', 'positions_store_id_index');
                }
            });

            if ($vendorStoresExists && Schema::hasColumn('positions', 'store_id')) {
                try {
                    Schema::table('positions', function (Blueprint $table) {
                        $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว - ข้าม
                }
            }
        }

        // เพิ่ม store_id ให้ตาราง employees
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'store_id')) {
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id', 'employees_store_id_index');
                }
            });

            if ($vendorStoresExists && Schema::hasColumn('employees', 'store_id')) {
                try {
                    Schema::table('employees', function (Blueprint $table) {
                        $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว - ข้าม
                }
            }
        }

        // เพิ่ม store_id ให้ตาราง attendance_records (ถ้ามี)
        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('attendance_records', 'store_id')) {
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id', 'attendance_records_store_id_idx');
                }
            });

            if ($vendorStoresExists && Schema::hasColumn('attendance_records', 'store_id')) {
                try {
                    Schema::table('attendance_records', function (Blueprint $table) {
                        $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว - ข้าม
                }
            }
        }

        // เพิ่ม store_id ให้ตาราง leave_requests (ถ้ามี)
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_requests', 'store_id')) {
                    $table->unsignedBigInteger('store_id')->nullable()->after('id');
                    $table->index('store_id', 'leave_requests_store_id_index');
                }
            });

            if ($vendorStoresExists && Schema::hasColumn('leave_requests', 'store_id')) {
                try {
                    Schema::table('leave_requests', function (Blueprint $table) {
                        $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key อาจมีอยู่แล้ว - ข้าม
                }
            }
        }

        // work_shifts มี store_id อยู่แล้วจาก migration ก่อนหน้า - ข้าม
    }

    /**
     * Reverse the migrations.
     * ลบ store_id ออกจากตาราง ERP
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

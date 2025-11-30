<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง pos_staff_assignments
     * ตารางสำหรับกำหนดพนักงานขาย POS และสิทธิ์การเข้าถึง
     */
    public function up(): void
    {
        if (Schema::hasTable('pos_staff_assignments')) {
            return;
        }

        Schema::create('pos_staff_assignments', function (Blueprint $table) {
            $table->id();
            // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained()
            // เพราะตารางที่อ้างอิงอาจยังไม่มีในขณะที่ migration นี้รัน
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('pos_device_id')->nullable();
            $table->unsignedBigInteger('work_shift_id')->nullable();
            $table->unsignedBigInteger('store_id');

            // ข้อมูลพนักงานขาย
            $table->string('staff_code')->unique(); // รหัสพนักงานขาย
            $table->string('pin_code', 6)->nullable(); // PIN 4-6 หลัก สำหรับ login เร็ว
            $table->string('rfid_tag')->nullable(); // RFID card
            $table->string('fingerprint_id')->nullable(); // ลายนิ้วมือ

            // สิทธิ์การเข้าถึงเมนู POS
            $table->json('pos_permissions')->nullable(); // ["sales", "refund", "discount", "void", "reports"]

            // การตั้งค่า
            $table->decimal('max_discount_percent', 5, 2)->default(0); // ส่วนลดสูงสุดที่ให้ได้ (%)
            $table->decimal('max_discount_amount', 10, 2)->default(0); // ส่วนลดสูงสุดที่ให้ได้ (บาท)
            $table->boolean('can_void_transaction')->default(false);
            $table->boolean('can_refund')->default(false);
            $table->boolean('can_open_drawer')->default(false);
            $table->boolean('can_view_reports')->default(false);
            $table->boolean('can_manage_inventory')->default(false);

            // กำหนดการทำงาน
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->json('working_days')->nullable(); // [0,1,2,3,4,5,6] (0=Sunday)

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // พนักงานหลักของเครื่อง

            // สถิติ
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index(['employee_id', 'is_active']);
            $table->index('pin_code');
            $table->index('rfid_tag');
        });

        // เพิ่ม foreign keys ถ้าตารางที่อ้างอิงมีอยู่แล้ว
        if (Schema::hasTable('employees')) {
            Schema::table('pos_staff_assignments', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pos_devices')) {
            Schema::table('pos_staff_assignments', function (Blueprint $table) {
                $table->foreign('pos_device_id')->references('id')->on('pos_devices')->onDelete('set null');
            });
        }

        if (Schema::hasTable('work_shifts')) {
            Schema::table('pos_staff_assignments', function (Blueprint $table) {
                $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('set null');
            });
        }

        if (Schema::hasTable('vendor_stores')) {
            Schema::table('pos_staff_assignments', function (Blueprint $table) {
                $table->foreign('store_id')->references('id')->on('vendor_stores')->onDelete('cascade');
            });
        }
    }

    /**
     * ลบตาราง pos_staff_assignments
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_staff_assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง database_backups - ข้อมูลการสำรองฐานข้อมูล
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name'); // ชื่อไฟล์ backup
            $table->string('backup_path'); // ตำแหน่งไฟล์
            $table->string('backup_type')->default('full'); // full, partial, incremental
            $table->bigInteger('file_size')->default(0); // ขนาดไฟล์ (bytes)
            $table->string('compression')->default('gzip'); // gzip, zip, none
            $table->string('status')->default('completed'); // processing, completed, failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type')->default('manual'); // manual, auto, pre_migration
            $table->json('database_info')->nullable(); // ข้อมูลฐานข้อมูล (tables, size, etc.)
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // เวลาที่ใช้ backup (วินาที)
            $table->text('error_message')->nullable();
            $table->boolean('is_verified')->default(false); // ตรวจสอบความถูกต้องแล้ว
            $table->timestamp('verified_at')->nullable();
            $table->boolean('auto_delete')->default(false); // ลบอัตโนมัติเมื่อครบกำหนด
            $table->timestamp('delete_after')->nullable(); // ลบหลังจากวันที่
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('backup_type');
            $table->index('delete_after');
        });

        // ตาราง system_updates - ประวัติการอัพเดทระบบ
        Schema::create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('update_number')->unique(); // รหัสอัพเดท (UP-YYYYMMDD-XXXXX)
            $table->string('version_from'); // เวอร์ชันเดิม
            $table->string('version_to'); // เวอร์ชันใหม่
            $table->string('update_type')->default('minor'); // major, minor, patch, hotfix
            $table->text('description'); // คำอธิบายการอัพเดท
            $table->json('changes')->nullable(); // รายการการเปลี่ยนแปลง
            $table->string('status')->default('pending'); // pending, running, completed, failed, rolled_back
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('database_backup_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('total_steps')->default(0); // จำนวนขั้นตอนทั้งหมด
            $table->integer('completed_steps')->default(0); // ขั้นตอนที่เสร็จแล้ว
            $table->integer('failed_steps')->default(0); // ขั้นตอนที่ล้มเหลว
            $table->decimal('progress_percentage', 5, 2)->default(0); // ความคืบหน้า %
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->json('migration_files')->nullable(); // ไฟล์ migration ที่รัน
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->boolean('requires_downtime')->default(false); // ต้องปิดระบบหรือไม่
            $table->timestamp('downtime_started_at')->nullable();
            $table->timestamp('downtime_ended_at')->nullable();
            $table->text('rollback_reason')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('update_type');
        });

        // ตาราง migration_logs - บันทึกรายละเอียดการทำ migration แต่ละขั้นตอน
        Schema::create('migration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_update_id')->constrained()->cascadeOnDelete();
            $table->string('migration_file'); // ชื่อไฟล์ migration
            $table->string('migration_name'); // ชื่อ migration
            $table->integer('batch')->default(0); // batch number
            $table->string('status')->default('pending'); // pending, running, completed, failed, skipped
            $table->integer('step_number')->default(0); // ลำดับขั้นตอน
            $table->text('description')->nullable(); // คำอธิบายขั้นตอน
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_milliseconds')->nullable();
            $table->integer('rows_affected')->nullable(); // จำนวนแถวที่ได้รับผลกระทบ
            $table->text('sql_executed')->nullable(); // SQL ที่ทำงาน
            $table->text('output')->nullable(); // ผลลัพธ์
            $table->text('error_message')->nullable();
            $table->json('error_trace')->nullable();
            $table->boolean('can_rollback')->default(true); // สามารถย้อนกลับได้
            $table->text('rollback_sql')->nullable(); // SQL สำหรับ rollback
            $table->timestamps();

            $table->index(['system_update_id', 'step_number']);
            $table->index(['status', 'created_at']);
        });

        // ตาราง system_health_checks - ตรวจสอบสุขภาพระบบ
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_type'); // database, storage, cache, queue, api, dependencies
            $table->string('status')->default('healthy'); // healthy, warning, critical, down
            $table->text('message')->nullable();
            $table->json('metrics')->nullable(); // ข้อมูลต่างๆ เช่น response time, disk usage, etc.
            $table->decimal('response_time_ms', 10, 2)->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['check_type', 'checked_at']);
            $table->index(['status', 'checked_at']);
        });

        // ตาราง system_maintenance_modes - โหมดปิดปรับปรุงระบบ
        Schema::create('system_maintenance_modes', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // หัวข้อ
            $table->text('message'); // ข้อความแจ้งผู้ใช้
            $table->foreignId('system_update_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(false);
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->json('allowed_ips')->nullable(); // IP ที่อนุญาตให้เข้าใช้งาน
            $table->json('allowed_users')->nullable(); // User ID ที่อนุญาต
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('is_active');
            $table->index('scheduled_start');
        });

        // ตาราง system_notifications - แจ้งเตือนระบบ
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // update, maintenance, alert, info
            $table->string('severity')->default('info'); // info, warning, error, critical
            $table->string('title');
            $table->text('message');
            $table->json('action_url')->nullable(); // URL ที่ให้คลิก
            $table->string('target_audience')->default('all'); // all, admins, vendors, customers
            $table->foreignId('system_update_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
            $table->index(['target_audience', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('system_maintenance_modes');
        Schema::dropIfExists('system_health_checks');
        Schema::dropIfExists('migration_logs');
        Schema::dropIfExists('system_updates');
        Schema::dropIfExists('database_backups');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง membership_retention_repairs ถ้ายังไม่มี
 *
 * Migration นี้แก้ไขปัญหาที่ตารางหายไปในบาง environment
 * โดยจะสร้างตารางใหม่เฉพาะกรณีที่ยังไม่มีตารางอยู่
 *
 * @see App\Models\MembershipRetentionRepair
 * @see App\Services\MembershipRetentionService
 */
return new class extends Migration
{
    /**
     * สร้างตาราง membership_retention_repairs หากยังไม่มี
     */
    public function up(): void
    {
        // ตรวจสอบและสร้างตาราง membership_retention_repairs
        if (! Schema::hasTable('membership_retention_repairs')) {
            Schema::create('membership_retention_repairs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->string('repair_period_month', 7); // เดือนที่ซ่อม YYYY-MM
                $table->decimal('points_needed', 10, 2); // แต้มที่ต้องซ่อม
                $table->decimal('repair_cost', 10, 2); // ค่าใช้จ่ายในการซ่อม
                $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
                $table->date('repaired_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'repair_period_month'], 'mrr_user_period_idx');
                $table->index('payment_status', 'mrr_payment_idx');
            });
        }
    }

    /**
     * ลบตาราง membership_retention_repairs
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_retention_repairs');
    }
};

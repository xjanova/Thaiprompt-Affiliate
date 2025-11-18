<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง ai_core_quotas
     *
     * ตารางนี้เก็บข้อมูลโควต้าแบบละเอียด
     * แยกตาม period และ feature เพื่อการจัดการที่ยืดหยุ่น
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_core_quotas')) {
            return;
        }

        Schema::create('ai_core_quotas', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->constrained('ai_core_tenants')
                ->onDelete('cascade')
                ->comment('Tenant เจ้าของโควต้า');

            $table->foreignId('feature_id')
                ->constrained('ai_core_features')
                ->onDelete('cascade')
                ->comment('Feature ที่มีโควต้า');

            // Period information
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'])->comment('ประเภทช่วงเวลา');
            $table->date('period_start')->comment('วันที่เริ่มต้น period');
            $table->date('period_end')->comment('วันที่สิ้นสุด period');

            // Quota limits
            $table->integer('quota_limit')->comment('จำนวนโควต้าที่กำหนด');
            $table->integer('quota_used')->default(0)->comment('จำนวนโควต้าที่ใช้ไปแล้ว');
            $table->integer('quota_remaining')->storedAs('quota_limit - quota_used')->comment('จำนวนโควต้าที่เหลือ');

            // Overage tracking
            $table->boolean('allow_overage')->default(false)->comment('อนุญาตให้เกินโควต้าได้');
            $table->integer('overage_used')->default(0)->comment('จำนวนที่ใช้เกินโควต้า');
            $table->decimal('overage_price', 10, 4)->nullable()->comment('ราคาต่อหน่วยที่เกิน');

            // Bonus quota
            $table->integer('bonus_quota')->default(0)->comment('โควต้าพิเศษที่ได้รับเพิ่ม');
            $table->text('bonus_reason')->nullable()->comment('เหตุผลที่ได้โควต้าพิเศษ');

            // Status
            $table->enum('status', ['active', 'expired', 'exhausted', 'suspended'])->default('active')->index()->comment('สถานะโควต้า');
            $table->timestamp('exhausted_at')->nullable()->comment('วันที่โควต้าหมด');

            // Auto-reset
            $table->boolean('auto_reset')->default(true)->comment('รีเซ็ตอัตโนมัติเมื่อครบ period');
            $table->timestamp('next_reset_at')->nullable()->comment('วันที่จะรีเซ็ตครั้งถัดไป');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'feature_id', 'period_type', 'period_start'], 'quota_period_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['feature_id', 'period_type']);
            $table->index(['period_end', 'status']);
            $table->index('next_reset_at');
        });
    }

    /**
     * ลบตาราง ai_core_quotas
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_core_quotas');
    }
};

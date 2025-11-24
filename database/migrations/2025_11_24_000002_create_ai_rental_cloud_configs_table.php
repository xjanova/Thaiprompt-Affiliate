<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_cloud_configs
     *
     * เก็บการตั้งค่า Cloud GPU สำหรับแต่ละ user
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_rental_cloud_configs')) {
            return;
        }

        Schema::create('ai_rental_cloud_configs', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่เป็นเจ้าของ config');

            $table->foreignId('cloud_provider_id')
                ->constrained('ai_rental_cloud_providers')
                ->onDelete('cascade')
                ->comment('Cloud provider ที่ใช้');

            // ข้อมูลการตั้งค่า
            $table->string('config_name')->comment('ชื่อ config');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // ข้อมูล API
            $table->text('api_key')->nullable()->comment('API key (encrypted)');
            $table->text('api_secret')->nullable()->comment('API secret (encrypted)');
            $table->string('api_endpoint')->nullable()->comment('API endpoint URL');

            // GPU Configuration
            $table->string('gpu_type')->nullable()->comment('ประเภท GPU ที่เลือก');
            $table->integer('gpu_count')->default(1)->comment('จำนวน GPU');
            $table->integer('memory_gb')->nullable()->comment('RAM (GB)');
            $table->integer('storage_gb')->nullable()->comment('พื้นที่เก็บข้อมูล (GB)');

            // Environment
            $table->string('python_version')->default('3.10')
                ->comment('Python version');
            $table->json('installed_packages')->nullable()
                ->comment('Python packages ที่ติดตั้ง');
            $table->string('cuda_version')->nullable()
                ->comment('CUDA version');

            // Status
            $table->enum('status', ['draft', 'active', 'paused', 'error', 'deleted'])
                ->default('draft')
                ->comment('สถานะ config');
            $table->boolean('is_default')->default(false)
                ->comment('เป็น config หลักหรือไม่');
            $table->timestamp('last_used_at')->nullable()
                ->comment('ใช้งานล่าสุดเมื่อ');

            // ข้อมูลการใช้งาน
            $table->integer('total_deployments')->default(0)
                ->comment('จำนวนครั้งที่ deploy');
            $table->decimal('total_cost_usd', 10, 2)->default(0)
                ->comment('ค่าใช้จ่ายรวม (USD)');
            $table->integer('total_gpu_hours')->default(0)
                ->comment('ชั่วโมง GPU รวม');

            // การตั้งค่าเพิ่มเติม
            $table->json('settings')->nullable()
                ->comment('การตั้งค่าเพิ่มเติม (JSON)');
            $table->text('notes')->nullable()
                ->comment('บันทึกเพิ่มเติม');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('cloud_provider_id');
            $table->index('status');
            $table->index('is_default');
            $table->unique(['user_id', 'config_name', 'cloud_provider_id'], 'user_config_unique');
        });
    }

    /**
     * ลบตาราง ai_rental_cloud_configs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_cloud_configs');
    }
};

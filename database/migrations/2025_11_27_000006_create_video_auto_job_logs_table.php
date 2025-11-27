<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_job_logs
     * เก็บ log การทำงานของแต่ละ job
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_job_logs')) {
            return;
        }

        Schema::create('video_auto_job_logs', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->unsignedBigInteger('job_id')->comment('Job ที่เกี่ยวข้อง');
            $table->unsignedBigInteger('project_id')->nullable()->comment('โปรเจกต์ (denormalized)');

            // ข้อมูล Log
            $table->enum('level', [
                'debug',
                'info',
                'notice',
                'warning',
                'error',
                'critical',
                'alert',
                'emergency'
            ])->default('info')->index();

            $table->string('step')->nullable()->comment('ขั้นตอนที่ log');
            $table->text('message')->comment('ข้อความ log');
            $table->json('context')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // API Related
            $table->string('api_service')->nullable()->comment('API service ที่ใช้: suno, freepik, youtube, etc.');
            $table->string('api_endpoint')->nullable()->comment('Endpoint ที่เรียก');
            $table->string('api_method')->nullable()->comment('HTTP Method');
            $table->unsignedSmallInteger('api_status_code')->nullable()->comment('HTTP Status Code');
            $table->unsignedInteger('api_response_time')->nullable()->comment('Response time (ms)');
            $table->json('api_request')->nullable()->comment('Request data (sanitized)');
            $table->json('api_response')->nullable()->comment('Response data (sanitized)');

            // Resource
            $table->unsignedBigInteger('memory_usage')->nullable()->comment('Memory ที่ใช้ (bytes)');
            $table->decimal('cpu_usage', 5, 2)->nullable()->comment('CPU usage %');

            $table->timestamp('logged_at')->useCurrent()->index();

            // Indexes
            $table->index(['job_id', 'level', 'logged_at'], 'vajl_job_level_time_idx');
            $table->index(['project_id', 'logged_at'], 'vajl_project_time_idx');
            $table->index(['api_service', 'logged_at'], 'vajl_api_time_idx');

            // Foreign keys
            $table->foreign('job_id')
                ->references('id')
                ->on('video_auto_jobs')
                ->onDelete('cascade');

            if (Schema::hasTable('video_auto_projects')) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('video_auto_projects')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * ลบตาราง video_auto_job_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_job_logs');
    }
};

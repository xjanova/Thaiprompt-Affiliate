<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: สร้างตาราง page_templates
 *
 * ⚠️ NOTE: ไม่ใช้ constrained('users') เพราะตาราง users อาจยังไม่มี
 * Foreign key จะถูกเพิ่มทีหลังเมื่อตาราง users มีแล้ว
 */
return new class extends Migration
{
    /**
     * สร้างตาราง page_templates
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('page_templates')) {
            return;
        }

        Schema::create('page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            // ⚠️ ไม่ใช้ constrained() เพราะ users อาจยังไม่มี
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_default');
            $table->index('is_active');
            $table->index('created_by');
        });

        // เพิ่ม foreign key ถ้าตาราง users มีอยู่แล้ว
        if (Schema::hasTable('users')) {
            Schema::table('page_templates', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * ลบตาราง page_templates
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('page_templates');
    }
};

<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟิลด์สำหรับ cascade dispatch, rider preferences, broadcast ร้านค้า
     */
    public function up(): void
    {
        // === riders: เพิ่ม rider preferences ===
        Schema::table('riders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'riders', 'preferred_job_types', function ($table) {
                $table->json('preferred_job_types')->nullable()->after('service_categories')
                    ->comment('ประเภทงานที่ต้องการ เช่น ["fresh_market","food"]');
            });
            $this->safeAddColumn($table, 'riders', 'preferred_radius_km', function ($table) {
                $table->decimal('preferred_radius_km', 5, 2)->nullable()->after('preferred_job_types')
                    ->comment('รัศมีสูงสุดที่ยอมรับงาน (กม.)');
            });
            $this->safeAddColumn($table, 'riders', 'preferred_min_fee', function ($table) {
                $table->decimal('preferred_min_fee', 8, 2)->nullable()->after('preferred_radius_km')
                    ->comment('ค่าส่งขั้นต่ำที่ยอมรับ (บาท)');
            });
        });

        // === rider_jobs: เพิ่ม dispatch tracking ===
        Schema::table('rider_jobs', function (Blueprint $table) {
            $this->safeAddColumn($table, 'rider_jobs', 'dispatch_type', function ($table) {
                $table->string('dispatch_type', 20)->default('auto')->after('status')
                    ->comment('วิธีจ่ายงาน: auto=ทีละคน, broadcast=ส่งทุกคน');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'dispatch_attempts', function ($table) {
                $table->json('dispatch_attempts')->nullable()->after('dispatch_type')
                    ->comment('ประวัติการเสนองาน [{"rider_id":1,"sent_at":"..","status":"rejected"}]');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'current_offer_rider_id', function ($table) {
                $table->unsignedBigInteger('current_offer_rider_id')->nullable()->after('dispatch_attempts')
                    ->comment('ไรเดอร์ที่กำลังพิจารณางานอยู่');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'offer_expires_at', function ($table) {
                $table->timestamp('offer_expires_at')->nullable()->after('current_offer_rider_id')
                    ->comment('หมดเวลาพิจารณา (2 นาที)');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'offer_sent_at', function ($table) {
                $table->timestamp('offer_sent_at')->nullable()->after('offer_expires_at')
                    ->comment('เวลาที่ส่ง offer ล่าสุด');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'candidate_riders', function ($table) {
                $table->json('candidate_riders')->nullable()->after('offer_sent_at')
                    ->comment('รายการ rider_id ที่ยังไม่ได้เสนอ');
            });
        });

        // === fresh_market_sellers: เพิ่ม broadcast credits ===
        Schema::table('fresh_market_sellers', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fresh_market_sellers', 'broadcast_credits', function ($table) {
                $table->unsignedInteger('broadcast_credits')->default(0)->after('rating_count')
                    ->comment('จำนวน broadcast คงเหลือ');
            });
            $this->safeAddColumn($table, 'fresh_market_sellers', 'broadcast_package_type', function ($table) {
                $table->string('broadcast_package_type', 50)->nullable()->after('broadcast_credits')
                    ->comment('ประเภทแพ็กเกจ: starter|pro|unlimited');
            });
            $this->safeAddColumn($table, 'fresh_market_sellers', 'broadcast_package_expires_at', function ($table) {
                $table->timestamp('broadcast_package_expires_at')->nullable()->after('broadcast_package_type')
                    ->comment('วันหมดอายุแพ็กเกจ broadcast');
            });
        });

        // === fresh_market_settings: เพิ่มค่า config dispatch/broadcast ===
        Schema::table('fresh_market_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fresh_market_settings', 'rider_offer_timeout_seconds', function ($table) {
                $table->unsignedInteger('rider_offer_timeout_seconds')->default(120)->after('gps_warning_max')
                    ->comment('เวลาให้ไรเดอร์ตอบรับ (วินาที) default 2 นาที');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'broadcast_max_radius_km', function ($table) {
                $table->decimal('broadcast_max_radius_km', 5, 2)->default(10.00)->after('rider_offer_timeout_seconds')
                    ->comment('รัศมีสูงสุดที่ broadcast (กม.)');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'max_dispatch_attempts', function ($table) {
                $table->unsignedInteger('max_dispatch_attempts')->default(5)->after('broadcast_max_radius_km')
                    ->comment('จำนวนครั้งสูงสุดในการเสนองาน cascade');
            });
        });
    }

    /**
     * ลบฟิลด์ที่เพิ่ม
     */
    public function down(): void
    {
        $this->safeDropColumn('riders', ['preferred_job_types', 'preferred_radius_km', 'preferred_min_fee']);
        $this->safeDropColumn('rider_jobs', ['dispatch_type', 'dispatch_attempts', 'current_offer_rider_id', 'offer_expires_at', 'offer_sent_at', 'candidate_riders']);
        $this->safeDropColumn('fresh_market_sellers', ['broadcast_credits', 'broadcast_package_type', 'broadcast_package_expires_at']);
        $this->safeDropColumn('fresh_market_settings', ['rider_offer_timeout_seconds', 'broadcast_max_radius_km', 'max_dispatch_attempts']);
    }
};

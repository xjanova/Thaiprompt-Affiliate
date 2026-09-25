<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ปรับโครงสร้างระบบไรเดอร์ให้พร้อมเปิดใช้งานจริง (2026-09-25)
 *
 * rider_jobs:
 *   - rider_id เป็น nullable (งาน pending ยังไม่มีไรเดอร์ — เดิม NOT NULL ทำให้สร้างงานไม่ได้เลย)
 *   - FK rider_id เปลี่ยนเป็น ON DELETE SET NULL (เก็บประวัติงานไว้แม้ลบไรเดอร์)
 *   - cancelled_by enum เพิ่ม admin, seller, buyer (คงค่าเดิม rider, customer, system)
 *   - source_type/source_id = ออเดอร์ต้นทาง (morph) ที่ implement RiderDeliverable
 *   - COD: cod_amount, cod_collected_at, cod_settled_at
 *   - เงินไรเดอร์: earnings_settled_at (กันจ่ายซ้ำ)
 *   - ส่งไม่สำเร็จ: failed_at, failure_reason, failure_note, failure_proof_image
 *   - คืนงาน: release_count
 *   - พิกัดตอนส่งจริง: delivered_latitude, delivered_longitude
 *   - พื้นที่ปลายทางแบบหยาบ (โชว์ก่อนรับงาน): delivery_area
 *   - รอบการกระจายงาน: dispatch_radius_km, dispatch_round, last_dispatched_at
 *   - ลูกค้าแชร์ตำแหน่งให้ไรเดอร์ (ต้องยินยอมเอง): customer_share_location + customer_last_*
 *
 * riders:
 *   - suspension_reason, suspended_at, suspended_by, rejected_at, rejected_by
 *   - share_location_consent_at (ยินยอมแชร์ตำแหน่งให้ลูกค้าระหว่างงาน — ต้องมีก่อนรับงานแรก)
 *
 * prod ตอนนี้ riders = 0, rider_jobs = 0 แถว → เปลี่ยนโครงสร้างได้ปลอดภัย
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // === 1. rider_jobs.rider_id → nullable + FK ON DELETE SET NULL ===
        if ($this->columnIsNotNullable('rider_jobs', 'rider_id')) {
            if ($isMysql) {
                if ($this->foreignKeyExists('rider_jobs', 'rider_jobs_rider_id_foreign')) {
                    DB::statement('ALTER TABLE `rider_jobs` DROP FOREIGN KEY `rider_jobs_rider_id_foreign`');
                }
                DB::statement('ALTER TABLE `rider_jobs` MODIFY `rider_id` BIGINT UNSIGNED NULL');
            } else {
                Schema::table('rider_jobs', function (Blueprint $table) {
                    $table->unsignedBigInteger('rider_id')->nullable()->change();
                });
            }
        }

        if ($isMysql && ! $this->foreignKeyExists('rider_jobs', 'rider_jobs_rider_id_foreign')) {
            DB::statement('ALTER TABLE `rider_jobs` ADD CONSTRAINT `rider_jobs_rider_id_foreign` FOREIGN KEY (`rider_id`) REFERENCES `riders` (`id`) ON DELETE SET NULL');
        }

        // === 2. cancelled_by enum += admin, seller, buyer (คงค่าเดิมทั้งหมด) ===
        if ($isMysql && Schema::hasColumn('rider_jobs', 'cancelled_by')) {
            DB::statement("ALTER TABLE `rider_jobs` MODIFY `cancelled_by` ENUM('rider','customer','system','admin','seller','buyer') NULL DEFAULT NULL");
        }

        // === 3. rider_jobs: คอลัมน์ใหม่ ===
        Schema::table('rider_jobs', function (Blueprint $table) {
            $this->safeAddColumn($table, 'rider_jobs', 'source_type', function ($table) {
                $table->string('source_type', 100)->nullable()->after('job_type')
                    ->comment('คลาสออเดอร์ต้นทาง (RiderDeliverable)');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'source_id', function ($table) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')
                    ->comment('id ออเดอร์ต้นทาง');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'delivery_area', function ($table) {
                $table->string('delivery_area', 255)->nullable()->after('delivery_notes')
                    ->comment('พื้นที่ปลายทางแบบหยาบ (เขต/อำเภอ จังหวัด) โชว์ก่อนรับงาน');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'cod_amount', function ($table) {
                $table->decimal('cod_amount', 10, 2)->default(0)->after('platform_fee')
                    ->comment('เงินสดที่ไรเดอร์ต้องเก็บจากผู้ซื้อ (0 = จ่ายล่วงหน้าแล้ว)');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'cod_collected_at', function ($table) {
                $table->timestamp('cod_collected_at')->nullable()->after('cod_amount');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'cod_settled_at', function ($table) {
                $table->timestamp('cod_settled_at')->nullable()->after('cod_collected_at')
                    ->comment('หักเงิน COD จากวอลเลตไรเดอร์แล้ว');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'earnings_settled_at', function ($table) {
                $table->timestamp('earnings_settled_at')->nullable()->after('cod_settled_at')
                    ->comment('บันทึกรายได้/สถิติไรเดอร์แล้ว (กันจ่ายซ้ำ)');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'failed_at', function ($table) {
                $table->timestamp('failed_at')->nullable()->after('cancelled_at');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'failure_reason', function ($table) {
                $table->string('failure_reason', 255)->nullable()->after('failed_at')
                    ->comment('รหัสเหตุผลส่งไม่สำเร็จ');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'failure_note', function ($table) {
                $table->text('failure_note')->nullable()->after('failure_reason');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'failure_proof_image', function ($table) {
                $table->string('failure_proof_image', 255)->nullable()->after('failure_note');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'release_count', function ($table) {
                $table->unsignedInteger('release_count')->default(0)->after('failure_proof_image')
                    ->comment('จำนวนครั้งที่ไรเดอร์คืนงาน');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'delivered_latitude', function ($table) {
                $table->decimal('delivered_latitude', 10, 8)->nullable()->after('delivered_at');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'delivered_longitude', function ($table) {
                $table->decimal('delivered_longitude', 11, 8)->nullable()->after('delivered_latitude');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'dispatch_radius_km', function ($table) {
                $table->decimal('dispatch_radius_km', 6, 2)->nullable()->after('candidate_riders')
                    ->comment('รัศมีกระจายงานรอบล่าสุด (กม.)');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'dispatch_round', function ($table) {
                $table->unsignedSmallInteger('dispatch_round')->default(0)->after('dispatch_radius_km');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'last_dispatched_at', function ($table) {
                $table->timestamp('last_dispatched_at')->nullable()->after('dispatch_round');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'customer_share_location', function ($table) {
                $table->boolean('customer_share_location')->default(false)->after('buyer_line_user_id')
                    ->comment('ลูกค้ายินยอมแชร์ตำแหน่งให้ไรเดอร์ระหว่างส่ง');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'customer_last_latitude', function ($table) {
                $table->decimal('customer_last_latitude', 10, 8)->nullable()->after('customer_share_location');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'customer_last_longitude', function ($table) {
                $table->decimal('customer_last_longitude', 11, 8)->nullable()->after('customer_last_latitude');
            });
            $this->safeAddColumn($table, 'rider_jobs', 'customer_location_at', function ($table) {
                $table->timestamp('customer_location_at')->nullable()->after('customer_last_longitude');
            });
        });

        $this->safeAddIndex('rider_jobs', ['source_type', 'source_id'], 'rider_jobs_source_idx');
        $this->safeAddIndex('rider_jobs', ['status', 'rider_id'], 'rider_jobs_status_rider_idx');

        // === 4. riders: การระงับ/ปฏิเสธ + ความยินยอมแชร์ตำแหน่ง ===
        Schema::table('riders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'riders', 'rejected_at', function ($table) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            });
            $this->safeAddColumn($table, 'riders', 'rejected_by', function ($table) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            });
            $this->safeAddColumn($table, 'riders', 'suspension_reason', function ($table) {
                $table->text('suspension_reason')->nullable()->after('rejected_by');
            });
            $this->safeAddColumn($table, 'riders', 'suspended_at', function ($table) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            });
            $this->safeAddColumn($table, 'riders', 'suspended_by', function ($table) {
                $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
            });
            $this->safeAddColumn($table, 'riders', 'share_location_consent_at', function ($table) {
                $table->timestamp('share_location_consent_at')->nullable()->after('permissions_granted_at')
                    ->comment('ยินยอมให้ลูกค้าเห็นตำแหน่งระหว่างงาน');
            });
        });

        $this->safeAddIndex('riders', ['availability', 'last_location_update'], 'riders_avail_loc_idx');
    }

    public function down(): void
    {
        $this->safeDropIndex('riders', 'riders_avail_loc_idx');
        $this->safeDropColumn('riders', [
            'rejected_at', 'rejected_by', 'suspension_reason', 'suspended_at', 'suspended_by', 'share_location_consent_at',
        ]);

        $this->safeDropIndex('rider_jobs', 'rider_jobs_source_idx');
        $this->safeDropIndex('rider_jobs', 'rider_jobs_status_rider_idx');
        $this->safeDropColumn('rider_jobs', [
            'source_type', 'source_id', 'delivery_area', 'cod_amount', 'cod_collected_at', 'cod_settled_at',
            'earnings_settled_at', 'failed_at', 'failure_reason', 'failure_note', 'failure_proof_image',
            'release_count', 'delivered_latitude', 'delivered_longitude', 'dispatch_radius_km', 'dispatch_round',
            'last_dispatched_at', 'customer_share_location', 'customer_last_latitude', 'customer_last_longitude',
            'customer_location_at',
        ]);

        // ไม่ย้อน rider_id กลับเป็น NOT NULL และไม่ตัดค่า enum ออก
        // เพราะจะทำให้แถวที่ rider_id = null หรือ cancelled_by = admin/seller/buyer พังทันที
    }

    /**
     * เช็คว่าคอลัมน์ยังเป็น NOT NULL อยู่หรือไม่ (ใช้ Schema::getColumns ของ Laravel 11)
     */
    private function columnIsNotNullable(string $table, string $column): bool
    {
        foreach (Schema::getColumns($table) as $col) {
            if (($col['name'] ?? null) === $column) {
                return ! ($col['nullable'] ?? true);
            }
        }

        return false;
    }
};

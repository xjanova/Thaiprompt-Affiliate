<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04) — โครงฐานข้อมูลกลางของทั้งชุด
 *
 * - ส่งมอบของด้วยการสแกน QR ใส่กันสองฝ่าย (delivery_handovers) + ทางสำรองถ่ายรูป 2 รอบ
 * - เงินผู้ซื้อพักไว้ แบ่งให้ร้าน/ไรเดอร์/ผู้แนะนำเมื่อส่งมอบสำเร็จ (orders.settlement_deferred)
 * - โบนัสไรเดอร์ที่ร้านเติม + ร้านออกค่าส่งให้ (rider_bonus / rider_free_delivery)
 * - หัวใจไรเดอร์ 1 ดวงต่อออเดอร์ + ล็อกเรียกไรเดอร์คนโปรด (rider_hearts / preferred_rider_id)
 * - ระยะตามถนนจริง (rider_jobs.distance_source / route_polyline)
 * - รูปโปรไฟล์ถ่ายสดเก็บแบบ private แล้วแสดงพร้อมลายน้ำ (users.profile_photo_private_path)
 *
 * ตอนเขียน prod มีไรเดอร์ 0 คน งานไรเดอร์ 0 งาน — เพิ่มคอลัมน์ได้ปลอดภัย
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        // ===== งานไรเดอร์ =====
        Schema::table('rider_jobs', function (Blueprint $table) {
            // งานใหม่ต้องส่งมอบผ่านการสแกน (งานเก่าก่อนรอบนี้ไม่มี จึงใช้ปุ่มส่งของแบบเดิมได้)
            $this->safeAddColumn($table, 'rider_jobs', 'handover_required', fn ($t) => $t->boolean('handover_required')->default(false));
            // โบนัสที่ร้านเติมให้ไรเดอร์ (ไรเดอร์ได้เต็มจำนวน ร้านเป็นคนจ่าย)
            $this->safeAddColumn($table, 'rider_jobs', 'shop_bonus', fn ($t) => $t->decimal('shop_bonus', 10, 2)->default(0));
            // ที่มาของระยะทาง: valhalla | google | haversine
            $this->safeAddColumn($table, 'rider_jobs', 'distance_source', fn ($t) => $t->string('distance_source', 20)->nullable());
            // เส้นทางแบบ encoded polyline (precision 6) สำหรับวาดบนแผนที่
            $this->safeAddColumn($table, 'rider_jobs', 'route_polyline', fn ($t) => $t->text('route_polyline')->nullable());
            // ไรเดอร์ที่ผู้ซื้อล็อกเรียก (ได้สิทธิ์รับก่อนจนถึง preferred_until)
            $this->safeAddColumn($table, 'rider_jobs', 'preferred_rider_id', fn ($t) => $t->unsignedBigInteger('preferred_rider_id')->nullable());
            $this->safeAddColumn($table, 'rider_jobs', 'preferred_until', fn ($t) => $t->timestamp('preferred_until')->nullable());
            $this->safeAddColumn($table, 'rider_jobs', 'preferred_by_user_id', fn ($t) => $t->unsignedBigInteger('preferred_by_user_id')->nullable());
        });
        $this->safeAddIndex('rider_jobs', 'preferred_rider_id', 'rider_jobs_preferred_rider_idx');

        // ===== ไรเดอร์ =====
        Schema::table('riders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'riders', 'hearts_count', fn ($t) => $t->unsignedInteger('hearts_count')->default(0));
            // ให้ผู้ซื้อเห็นบนแผนที่ "ไรเดอร์ใกล้ฉัน" (ตำแหน่งเบลอ) — ปิดได้ในหน้าตั้งค่าไรเดอร์
            $this->safeAddColumn($table, 'riders', 'show_on_nearby', fn ($t) => $t->boolean('show_on_nearby')->default(true));
        });

        // ===== ออเดอร์ร้านค้า + ออเดอร์ตลาดสด =====
        foreach (['orders', 'fresh_market_orders'] as $orders) {
            Schema::table($orders, function (Blueprint $table) use ($orders) {
                $this->safeAddColumn($table, $orders, 'preferred_rider_id', fn ($t) => $t->unsignedBigInteger('preferred_rider_id')->nullable());
                // ค่าที่ล็อกไว้ตอนสั่ง: โบนัสไรเดอร์ที่ร้านจ่าย + ค่าส่งที่ร้านออกแทนผู้ซื้อ (หักจากรายได้ร้านตอนแบ่งเงิน)
                $this->safeAddColumn($table, $orders, 'rider_bonus_amount', fn ($t) => $t->decimal('rider_bonus_amount', 10, 2)->default(0));
                $this->safeAddColumn($table, $orders, 'delivery_subsidy_amount', fn ($t) => $t->decimal('delivery_subsidy_amount', 10, 2)->default(0));
                // true = ห้ามแบ่งเงินตอนจ่าย รอจนส่งมอบสำเร็จ (สแกนครบ หรือปลดอัตโนมัติ)
                $this->safeAddColumn($table, $orders, 'settlement_deferred', fn ($t) => $t->boolean('settlement_deferred')->default(false));
            });
        }

        // ===== ค่าตอบแทนไรเดอร์ที่ร้านตั้ง (ร้านค้า + ร้านตลาดสด) =====
        foreach (['vendor_stores', 'fresh_market_sellers'] as $stores) {
            Schema::table($stores, function (Blueprint $table) use ($stores) {
                $this->safeAddColumn($table, $stores, 'rider_bonus', fn ($t) => $t->decimal('rider_bonus', 8, 2)->default(0));
                $this->safeAddColumn($table, $stores, 'rider_bonus_peak', fn ($t) => $t->decimal('rider_bonus_peak', 8, 2)->default(0));
                // ร้านออกค่าส่งทั้งหมด (ลูกค้าเห็น "ส่งฟรี")
                $this->safeAddColumn($table, $stores, 'rider_free_delivery', fn ($t) => $t->boolean('rider_free_delivery')->default(false));
            });
        }

        // ===== รูปโปรไฟล์ถ่ายสด (เก็บ private · แสดงเฉพาะแบบมีลายน้ำ) =====
        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'profile_photo_private_path', fn ($t) => $t->string('profile_photo_private_path', 500)->nullable());
            $this->safeAddColumn($table, 'users', 'profile_photo_taken_at', fn ($t) => $t->timestamp('profile_photo_taken_at')->nullable());
        });

        // ===== การส่งมอบของ (สแกน QR ใส่กัน / รหัส 6 หลัก / รูป 2 รอบ) =====
        if (! Schema::hasTable('delivery_handovers')) {
            Schema::create('delivery_handovers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rider_job_id')->unique()->constrained('rider_jobs')->cascadeOnDelete();
                $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();

                // waiting | rider_confirmed | buyer_confirmed | completed | fallback_waiting
                // | fallback_pending_release | disputed | released | refunded
                $table->string('status', 30)->default('waiting');
                // วิธีที่ปิดงาน: qr | code | fallback | admin
                $table->string('method', 20)->nullable();

                // กุญแจลับต่องาน (เข้ารหัส) ใช้เซ็น QR ที่เปลี่ยนทุกนาที + รหัส 6 หลัก (hash)
                $table->text('secret');
                $table->string('code_hash', 100)->nullable();
                $table->unsignedTinyInteger('code_attempts')->default(0);
                $table->timestamp('code_locked_until')->nullable();

                // ไรเดอร์สแกน QR ของผู้ซื้อ (หรือกรอกรหัส) — พิสูจน์ว่าเจอผู้ซื้อตัวจริงที่จุดส่ง
                $table->timestamp('rider_confirmed_at')->nullable();
                $table->decimal('rider_confirm_latitude', 10, 7)->nullable();
                $table->decimal('rider_confirm_longitude', 10, 7)->nullable();
                $table->unsignedInteger('rider_confirm_distance_m')->nullable();
                // ผู้ซื้อสแกน QR ของไรเดอร์ — ยืนยันว่าได้รับของแล้ว
                $table->timestamp('buyer_confirmed_at')->nullable();

                // ทางสำรองเมื่อผู้ซื้อไม่สแกน: รูปรอบ 1 (ถึงจุดส่ง เริ่มนับรอ) → รูปรอบ 2 (รอครบแล้ว)
                $table->string('arrival_photo_path', 500)->nullable();
                $table->timestamp('arrival_photo_at')->nullable();
                $table->decimal('arrival_latitude', 10, 7)->nullable();
                $table->decimal('arrival_longitude', 10, 7)->nullable();
                $table->unsignedInteger('arrival_distance_m')->nullable();
                $table->timestamp('wait_until')->nullable();
                $table->string('waited_photo_path', 500)->nullable();
                $table->timestamp('waited_photo_at')->nullable();
                $table->decimal('waited_latitude', 10, 7)->nullable();
                $table->decimal('waited_longitude', 10, 7)->nullable();
                $table->timestamp('auto_release_at')->nullable();

                // ผู้ซื้อร้องเรียน (ไม่ได้รับของ) → แอดมินตัดสิน release | refund
                $table->timestamp('disputed_at')->nullable();
                $table->string('dispute_reason', 50)->nullable();
                $table->text('dispute_note')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('resolution', 20)->nullable();
                $table->text('resolution_note')->nullable();

                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'auto_release_at'], 'delivery_handovers_release_idx');
            });
        }

        // ===== หัวใจไรเดอร์ (1 ดวงต่อ 1 งานที่ส่งสำเร็จ ให้ได้เฉพาะผู้ซื้อของงานนั้น) =====
        if (! Schema::hasTable('rider_hearts')) {
            Schema::create('rider_hearts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rider_id')->constrained('riders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('rider_job_id')->unique()->constrained('rider_jobs')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['rider_id', 'user_id'], 'rider_hearts_pair_idx');
                $table->index(['user_id', 'rider_id'], 'rider_hearts_user_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_hearts');
        Schema::dropIfExists('delivery_handovers');

        $this->safeDropColumn('users', ['profile_photo_private_path', 'profile_photo_taken_at']);
        foreach (['vendor_stores', 'fresh_market_sellers'] as $stores) {
            $this->safeDropColumn($stores, ['rider_bonus', 'rider_bonus_peak', 'rider_free_delivery']);
        }
        foreach (['orders', 'fresh_market_orders'] as $orders) {
            $this->safeDropColumn($orders, ['preferred_rider_id', 'rider_bonus_amount', 'delivery_subsidy_amount', 'settlement_deferred']);
        }
        $this->safeDropColumn('riders', ['hearts_count', 'show_on_nearby']);
        $this->safeDropIndex('rider_jobs', 'rider_jobs_preferred_rider_idx');
        $this->safeDropColumn('rider_jobs', [
            'handover_required', 'shop_bonus', 'distance_source', 'route_polyline',
            'preferred_rider_id', 'preferred_until', 'preferred_by_user_id',
        ]);
    }
};

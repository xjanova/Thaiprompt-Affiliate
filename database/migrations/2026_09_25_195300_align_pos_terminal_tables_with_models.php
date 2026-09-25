<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ปรับตาราง pos_api_keys / pos_terminals ให้ตรงกับโมเดล PosApiKey / PosTerminal
 *
 * ที่มา: มี migration สร้างระบบ POS Terminal 2 ไฟล์ (2025_12_11_000001 และ 2025_12_11_120000)
 * ไฟล์แรกรันก่อน → สร้างตารางแบบเก่า (มี key_prefix NOT NULL / is_active แต่ไม่มี is_blocked, status ฯลฯ)
 * ไฟล์ที่สองเจอว่ามีตารางแล้วจึงข้ามไป → โมเดลอ้างคอลัมน์ที่ไม่มีจริง
 * ผลคือหน้า "เครื่อง POS" ของผู้ขาย สร้าง/บล็อก API Key ไม่ได้ (Unknown column 'is_blocked')
 *
 * แก้แบบเพิ่มคอลัมน์ที่ขาดเท่านั้น (idempotent — เช็ค hasColumn ทุกตัว, ไม่ลบ/ไม่แก้คอลัมน์เดิม)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ที่โมเดลใช้แต่ตารางจริงยังไม่มี
     */
    public function up(): void
    {
        if (Schema::hasTable('pos_api_keys')) {
            Schema::table('pos_api_keys', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_api_keys', 'key_prefix')) {
                    $table->string('key_prefix', 8)->nullable()->after('key');
                }
                if (! Schema::hasColumn('pos_api_keys', 'is_blocked')) {
                    $table->boolean('is_blocked')->default(false)->after('is_active');
                }
                if (! Schema::hasColumn('pos_api_keys', 'blocked_reason')) {
                    $table->string('blocked_reason')->nullable()->after('is_blocked');
                }
                if (! Schema::hasColumn('pos_api_keys', 'blocked_at')) {
                    $table->timestamp('blocked_at')->nullable()->after('blocked_reason');
                }
                if (! Schema::hasColumn('pos_api_keys', 'blocked_by')) {
                    $table->unsignedBigInteger('blocked_by')->nullable()->after('blocked_at');
                }
                if (! Schema::hasColumn('pos_api_keys', 'permissions')) {
                    $table->json('permissions')->nullable();
                }
                if (! Schema::hasColumn('pos_api_keys', 'rate_limit')) {
                    $table->unsignedInteger('rate_limit')->nullable();
                }
            });
        }

        if (Schema::hasTable('pos_terminals')) {
            $addedStatus = false;

            Schema::table('pos_terminals', function (Blueprint $table) use (&$addedStatus) {
                if (! Schema::hasColumn('pos_terminals', 'terminal_name')) {
                    $table->string('terminal_name')->nullable();
                }
                if (! Schema::hasColumn('pos_terminals', 'status')) {
                    $table->enum('status', ['pending', 'active', 'suspended', 'blocked'])->default('active');
                    $addedStatus = true;
                }
                if (! Schema::hasColumn('pos_terminals', 'is_verified')) {
                    $table->boolean('is_verified')->default(true);
                }
                if (! Schema::hasColumn('pos_terminals', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable();
                }
                if (! Schema::hasColumn('pos_terminals', 'last_ip_address')) {
                    $table->string('last_ip_address', 45)->nullable();
                }
                if (! Schema::hasColumn('pos_terminals', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (! Schema::hasColumn('pos_terminals', 'device_info')) {
                    $table->json('device_info')->nullable();
                }
            });

            // เครื่องที่เคยถูกปิด (is_active = 0) ต้องไม่กลายเป็น active หลังเพิ่มคอลัมน์ status
            if ($addedStatus && Schema::hasColumn('pos_terminals', 'is_active')) {
                DB::table('pos_terminals')->where('is_active', false)->update(['status' => 'suspended']);
            }
        }
    }

    /**
     * ย้อนกลับ: ลบเฉพาะคอลัมน์ที่ migration นี้เพิ่ม (ถ้ามีอยู่)
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_terminals')) {
            foreach (['terminal_name', 'status', 'is_verified', 'verified_at', 'last_ip_address', 'notes', 'device_info'] as $col) {
                // ตารางแบบใหม่ (จาก 2025_12_11_120000) มีคอลัมน์เหล่านี้อยู่แล้วตั้งแต่แรก — ห้ามลบ
                if (Schema::hasColumn('pos_terminals', $col) && Schema::hasColumn('pos_terminals', 'is_active')) {
                    Schema::table('pos_terminals', fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }

        if (Schema::hasTable('pos_api_keys') && Schema::hasColumn('pos_api_keys', 'max_terminals')) {
            foreach (['is_blocked', 'blocked_reason', 'blocked_at', 'blocked_by', 'permissions', 'rate_limit'] as $col) {
                if (Schema::hasColumn('pos_api_keys', $col)) {
                    Schema::table('pos_api_keys', fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }
    }
};

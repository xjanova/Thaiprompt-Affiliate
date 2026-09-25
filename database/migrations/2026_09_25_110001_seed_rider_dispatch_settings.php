<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ใส่ค่าเริ่มต้นของระบบไรเดอร์ลงตาราง settings (group = rider)
 *
 * - ใส่เฉพาะ key ที่ยังไม่มี (insertOrIgnore) → ค่าที่แอดมินแก้ไว้แล้วจะไม่ถูกทับ
 * - โค้ดอ่านผ่าน Setting::get(key, default) เสมอ ถึงไม่มีแถวก็ยังทำงานด้วยค่า default เดียวกัน
 * - รายการ key ทั้งหมดอยู่ที่ App\Services\DeliveryFeeCalculator::DEFAULTS (แหล่งความจริงเดียว)
 */
return new class extends Migration
{
    /**
     * key => [value, type]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $defaults = [
        'rider.dispatch_mode' => ['broadcast', 'string'],
        'rider.require_deposit' => ['0', 'boolean'],
        'rider.base_fee' => ['30', 'float'],
        'rider.per_km_fee' => ['10', 'float'],
        'rider.free_km' => ['2', 'float'],
        'rider.min_fee' => ['30', 'float'],
        'rider.max_distance_km' => ['15', 'float'],
        'rider.road_factor' => ['1.3', 'float'],
        'rider.rider_share_percent' => ['80', 'float'],
        'rider.offer_radius_km' => ['5', 'float'],
        'rider.max_offer_radius_km' => ['10', 'float'],
        'rider.max_dispatch_rounds' => ['3', 'integer'],
        'rider.rebroadcast_interval_minutes' => ['2', 'integer'],
        'rider.offer_timeout_seconds' => ['120', 'integer'],
        'rider.max_cod_amount' => ['2000', 'float'],
        'rider.pending_timeout_minutes' => ['10', 'integer'],
        'rider.location_fresh_minutes' => ['15', 'integer'],
        'rider.location_retention_days' => ['30', 'integer'],
        'rider.tracking_expiry_hours' => ['24', 'integer'],
        'rider.tracking_grace_minutes' => ['15', 'integer'],
        'rider.avg_speed_kmh' => ['25', 'float'],
        'rider.max_release_count' => ['3', 'integer'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($this->defaults as $key => [$value, $type]) {
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => 'rider',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // key มี unique index → แถวที่มีอยู่แล้วจะถูกข้าม ไม่ทับค่าที่แอดมินตั้ง
        DB::table('settings')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        // ไม่ลบค่าตั้งค่า — แอดมินอาจแก้ไว้แล้ว และโค้ดมีค่า default สำรองอยู่แล้ว
    }
};

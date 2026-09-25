<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เครื่องคำนวณราคากลาง (App\Services\Pricing\PricingEngine)
 *
 * 1) products.admin_gp_rate — อัตรา GP ที่ "แอดมิน" กำหนดให้สินค้าเฉพาะตัว (null = ใช้อัตราตามแพ็กเกจร้าน)
 *    ผู้ขายแก้ไม่ได้ (ไม่อยู่ใน $fillable ของ Product) — ต่างจาก products.commission_rate ที่ผู้ขายส่งมาเองได้
 *    และจะไม่ถูกใช้คิดเงินอีกต่อไป
 * 2) ค่าเริ่มต้นของ settings group 'pricing' — ใส่เฉพาะ key ที่ยังไม่มี ไม่ทับค่าที่แอดมินตั้งไว้
 *    (โค้ดอ่านผ่าน Setting::get(key, default) ถึงไม่มีแถวก็ทำงานด้วยค่าเดียวกัน)
 *
 *    🎁 คำสั่งเจ้าของ (2026-09-25) "GP ฟรีช่วงเปิดตัว":
 *       pricing.default_gp_rate = 0, pricing.min_gp_rate = 0, pricing.fresh_market_gp_rate = 0
 *       + pricing.gp_free = 1 (โปรฯ GP ฟรี — ทับอัตราตามแพ็กเกจ/ร้าน ยกเว้น admin_gp_rate ที่แอดมินตั้งรายสินค้า)
 *       + pricing.gp_free_until ว่าง = ฟรีจนกว่าแอดมินจะปิด/ตั้งวันสิ้นสุด (หน้าแอดมิน "ส่วนแบ่งรายได้ & GP")
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * key => [value, type]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $defaults = [
        'pricing.default_gp_rate' => ['0', 'float'],
        'pricing.min_gp_rate' => ['0', 'float'],
        'pricing.fresh_market_gp_rate' => ['0', 'float'],
        'pricing.gp_free' => ['1', 'boolean'],
        'pricing.gp_free_until' => ['', 'string'],
        'pricing.vat_rate' => ['7', 'float'],
        'pricing.referral_pool_percent' => ['0', 'float'],
        'pricing.official_shop_vat_registered' => ['1', 'boolean'],
    ];

    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->safeAddColumn($table, 'products', 'admin_gp_rate', function (Blueprint $table) {
                    $table->decimal('admin_gp_rate', 5, 2)
                        ->nullable()
                        ->after('commission_rate')
                        ->comment('อัตรา GP (%) ที่แอดมินกำหนดให้สินค้านี้ — null = ใช้อัตราตามแพ็กเกจร้าน');
                });
            });
        }

        if (Schema::hasTable('settings')) {
            $now = now();
            $rows = [];
            foreach ($this->defaults as $key => [$value, $type]) {
                $rows[] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'group' => 'pricing',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // key มี unique index → แถวที่มีอยู่แล้วถูกข้าม
            DB::table('settings')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $this->safeDropColumn('products', 'admin_gp_rate');

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', array_keys($this->defaults))->delete();
        }
    }
};

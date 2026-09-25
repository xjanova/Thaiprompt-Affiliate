<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ระบบเงิน e-commerce ก่อนเปิดตัว (workstream E2)
 *
 * 1) earnings_ledger: unique key เดิม (source_type, source_id, earning_type) ไม่รวม user_id
 *    → ออเดอร์ที่มีผู้ขาย 2 รายขึ้นไป ผู้ขายรายที่ 2 ชน duplicate key ทั้ง transaction rollback (audit G3)
 *    เปลี่ยนเป็น (source_type, source_id, earning_type, user_id) — สร้างตัวใหม่ก่อนแล้วค่อยลบตัวเก่า
 * 2) mlm_commissions.type เพิ่ม 'direct_referral' (MlmReferralBonusService ใช้ค่านี้แต่ enum ไม่มี — audit G12)
 * 3) vendor_subscriptions.status เพิ่ม 'pending' (subscription ที่รอชำระเงิน — เดิม insert แล้วพังใน strict mode)
 * 4) vendor_packages.is_custom_pricing — แพ็กเกจราคาพิเศษ (Enterprise ราคา 0) ต้องให้แอดมินกำหนด ไม่ใช่ "ฟรี"
 * 5) vendor_stores.vat_registered — ร้านจด VAT หรือไม่ (workstream D ก็เพิ่มคอลัมน์นี้ — เช็ค hasColumn ก่อนเสมอ)
 * 6) settings money.distribution_backfill_from — ตัว cron แบ่งเงินย้อนหลังจะไม่หยิบออเดอร์ที่จ่ายก่อนวันนี้
 *    (ออเดอร์เก่าก่อนเปิดระบบแบ่งเงินจริง ไม่ถูกจ่ายเงินให้ผู้ขายอัตโนมัติโดยไม่มีคนตรวจ)
 *
 * enum เปลี่ยนเฉพาะ MySQL และเก็บค่าเดิมทุกค่า
 */
return new class extends Migration
{
    use SafeMigration;

    private const LEDGER_OLD_UNIQUE = 'earnings_ledger_source_type_unique';

    private const LEDGER_NEW_UNIQUE = 'el_src_type_user_uq';

    public function up(): void
    {
        $this->upgradeEarningsLedgerUnique();
        $this->addDirectReferralType();
        $this->addPendingSubscriptionStatus();
        $this->addCustomPricingFlag();
        $this->addVatRegisteredToStores();
        $this->seedDistributionCutoff();
    }

    public function down(): void
    {
        // คืน unique เดิมได้เฉพาะเมื่อไม่มีออเดอร์หลายผู้ขายในตาราง — ถ้ามีจะคง unique ใหม่ไว้
        if (Schema::hasTable('earnings_ledger') && $this->indexExists('earnings_ledger', self::LEDGER_NEW_UNIQUE)) {
            $hasMultiSeller = DB::table('earnings_ledger')
                ->select('source_type', 'source_id', 'earning_type')
                ->groupBy('source_type', 'source_id', 'earning_type')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $hasMultiSeller) {
                if (! $this->indexExists('earnings_ledger', self::LEDGER_OLD_UNIQUE)) {
                    Schema::table('earnings_ledger', function (Blueprint $table) {
                        $table->unique(['source_type', 'source_id', 'earning_type'], self::LEDGER_OLD_UNIQUE);
                    });
                }
                Schema::table('earnings_ledger', function (Blueprint $table) {
                    $table->dropUnique(self::LEDGER_NEW_UNIQUE);
                });
            }
        }

        $this->safeDropColumn('vendor_packages', 'is_custom_pricing');

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'money.distribution_backfill_from')->delete();
        }

        // ไม่หด enum กลับ (อาจมีแถวที่ใช้ค่าใหม่แล้ว) และไม่ลบ vendor_stores.vat_registered (workstream D ใช้ร่วม)
    }

    /**
     * unique ใหม่รวม user_id → ผู้ขายแต่ละรายมี ledger ของตัวเองต่อออเดอร์
     */
    private function upgradeEarningsLedgerUnique(): void
    {
        if (! Schema::hasTable('earnings_ledger')) {
            return;
        }

        // สร้างตัวใหม่ก่อน (ตาราง prod ยังว่าง — แต่ต่อให้มีข้อมูล unique ใหม่ก็กว้างกว่าเดิมจึงไม่ชน)
        $this->safeAddIndex('earnings_ledger', ['source_type', 'source_id', 'earning_type', 'user_id'], self::LEDGER_NEW_UNIQUE, 'unique');

        if ($this->indexExists('earnings_ledger', self::LEDGER_OLD_UNIQUE)) {
            Schema::table('earnings_ledger', function (Blueprint $table) {
                $table->dropUnique(self::LEDGER_OLD_UNIQUE);
            });
        }
    }

    /**
     * เพิ่ม 'direct_referral' เข้า enum mlm_commissions.type (คงค่าเดิมทุกค่า + nullable ตามเดิม)
     */
    private function addDirectReferralType(): void
    {
        if (! Schema::hasTable('mlm_commissions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `mlm_commissions` WHERE Field = 'type'");
        if (! $column || str_contains((string) ($column->Type ?? ''), "'direct_referral'")) {
            return;
        }

        $values = $this->enumValues((string) $column->Type);
        $values[] = 'direct_referral';

        DB::statement('ALTER TABLE `mlm_commissions` MODIFY COLUMN `type` ENUM('.$this->quoteList($values).') NULL DEFAULT NULL');
    }

    /**
     * เพิ่ม 'pending' เข้า enum vendor_subscriptions.status (subscription ที่ยังไม่ชำระเงิน)
     */
    private function addPendingSubscriptionStatus(): void
    {
        if (! Schema::hasTable('vendor_subscriptions') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `vendor_subscriptions` WHERE Field = 'status'");
        if (! $column || str_contains((string) ($column->Type ?? ''), "'pending'")) {
            return;
        }

        $values = $this->enumValues((string) $column->Type);
        $values[] = 'pending';

        DB::statement('ALTER TABLE `vendor_subscriptions` MODIFY COLUMN `status` ENUM('.$this->quoteList($values).") NOT NULL DEFAULT 'active'");
    }

    /**
     * แพ็กเกจราคาพิเศษ — สมัครเองไม่ได้ แอดมินเป็นคนกำหนดให้ร้าน
     */
    private function addCustomPricingFlag(): void
    {
        if (! Schema::hasTable('vendor_packages')) {
            return;
        }

        Schema::table('vendor_packages', function (Blueprint $table) {
            $this->safeAddColumn($table, 'vendor_packages', 'is_custom_pricing', function (Blueprint $table) {
                $table->boolean('is_custom_pricing')
                    ->default(false)
                    ->after('is_default')
                    ->comment('แพ็กเกจราคาพิเศษ ต้องติดต่อทีมงาน (ห้ามถือว่าราคา 0 = ฟรี)');
            });
        });

        // Enterprise ที่ตั้งราคา 0 ไว้ = ราคาตามตกลง ไม่ใช่แพ็กเกจฟรี
        DB::table('vendor_packages')
            ->where('package_slug', 'enterprise')
            ->where('price', '<=', 0)
            ->update(['is_custom_pricing' => true]);
    }

    /**
     * ร้านจด VAT (ค่าเริ่มต้น = ไม่จด → ไม่หัก VAT จากยอดขายของร้าน)
     */
    private function addVatRegisteredToStores(): void
    {
        if (! Schema::hasTable('vendor_stores')) {
            return;
        }

        Schema::table('vendor_stores', function (Blueprint $table) {
            $this->safeAddColumn($table, 'vendor_stores', 'vat_registered', function (Blueprint $table) {
                $table->boolean('vat_registered')
                    ->default(false)
                    ->comment('ร้านจดทะเบียนภาษีมูลค่าเพิ่ม (หัก VAT 7/107 จากยอดขายเฉพาะร้านที่จด)');
            });
        });
    }

    /**
     * cron แบ่งเงินจะหยิบเฉพาะออเดอร์ที่ชำระตั้งแต่เวลานี้ (ออเดอร์เก่าให้แอดมินสั่งเองหลังตรวจ)
     */
    private function seedDistributionCutoff(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        try {
            DB::table('settings')->insertOrIgnore([
                'key' => 'money.distribution_backfill_from',
                'value' => now()->toDateTimeString(),
                'type' => 'string',
                'group' => 'money',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('money migration: seed distribution cutoff failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * แยกค่าจาก "enum('a','b')" → ['a', 'b']
     *
     * @return array<int, string>
     */
    private function enumValues(string $type): array
    {
        if (! preg_match('/^enum\((.*)\)$/i', trim($type), $m)) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $m[1], $matches);

        return array_values(array_unique(array_map(
            fn ($v) => str_replace("''", "'", $v),
            $matches[1] ?? []
        )));
    }

    /**
     * @param  array<int, string>  $values
     */
    private function quoteList(array $values): string
    {
        return implode(',', array_map(
            fn ($v) => "'".str_replace("'", "''", $v)."'",
            array_values(array_unique($values))
        ));
    }
};

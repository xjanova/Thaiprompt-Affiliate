<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * PlatformWallet Model
 *
 * กระเป๋าเงินของระบบ สำหรับเก็บรายได้ Platform
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property float $balance
 * @property float $total_income
 * @property float $total_expense
 * @property float $total_transferred
 * @property bool $is_active
 * @property string|null $description
 * @property array|null $settings
 */
class PlatformWallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'balance',
        'total_income',
        'total_expense',
        'total_transferred',
        'is_active',
        'description',
        'settings',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'total_income' => 'decimal:4',
        'total_expense' => 'decimal:4',
        'total_transferred' => 'decimal:4',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * ประเภทกระเป๋า
     */
    const TYPE_PLATFORM_FEE = 'platform_fee';

    const TYPE_VAT = 'vat';

    const TYPE_MLM_POOL = 'mlm_pool';

    const TYPE_RESERVE = 'reserve';

    const TYPE_REFUND_POOL = 'refund_pool';

    const TYPE_OTHER = 'other';

    /**
     * รายการ transactions ทั้งหมด
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class);
    }

    /**
     * รายการเข้า
     */
    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class)
            ->where('type', 'income');
    }

    /**
     * รายการออก
     */
    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class)
            ->where('type', 'expense');
    }

    /**
     * ดึงกระเป๋าตาม slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * ดึงกระเป๋าตามประเภท
     */
    public static function findByType(string $type): ?self
    {
        return static::where('type', $type)->where('is_active', true)->first();
    }

    /**
     * ดึงหรือสร้างกระเป๋า Fee
     */
    public static function getFeeWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'fee'],
            [
                'name' => 'ค่าธรรมเนียม Platform',
                'type' => self::TYPE_PLATFORM_FEE,
                'description' => 'กระเป๋าสำหรับเก็บค่าธรรมเนียม Platform จากการขาย',
            ]
        );
    }

    /**
     * ดึงหรือสร้างกระเป๋า VAT
     */
    public static function getVatWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'vat'],
            [
                'name' => 'ภาษี VAT',
                'type' => self::TYPE_VAT,
                'description' => 'กระเป๋าสำหรับเก็บภาษี VAT',
            ]
        );
    }

    /**
     * ดึงหรือสร้างกระเป๋า MLM Pool
     */
    public static function getMlmPoolWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'mlm_pool'],
            [
                'name' => 'กองทุน MLM Commission',
                'type' => self::TYPE_MLM_POOL,
                'description' => 'กระเป๋าสำหรับเก็บเงินคอมมิชชัน MLM รอจ่าย',
            ]
        );
    }

    /**
     * ดึงหรือสร้างกระเป๋า Admin Shop
     */
    public static function getAdminShopWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'admin_shop'],
            [
                'name' => 'รายได้ร้านแอดมิน',
                'type' => 'admin_shop',
                'description' => 'กระเป๋ารายได้จากการขายสินค้า Official Shop',
            ]
        );
    }

    /**
     * ดึงหรือสร้างกระเป๋า Admin Services
     */
    public static function getAdminServicesWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'admin_services'],
            [
                'name' => 'รายได้บริการแอดมิน',
                'type' => 'admin_services',
                'description' => 'กระเป๋ารายได้จากบริการต่างๆ ของแอดมิน',
            ]
        );
    }

    /**
     * ดึงหรือสร้างกระเป๋า Refund Pool
     */
    public static function getRefundPoolWallet(): self
    {
        return static::firstOrCreate(
            ['slug' => 'refund_pool'],
            [
                'name' => 'กองทุนคืนเงิน',
                'type' => self::TYPE_REFUND_POOL,
                'description' => 'กระเป๋าสำหรับคืนเงินลูกค้า',
            ]
        );
    }

    /**
     * เพิ่มเงินเข้ากระเป๋า
     *
     * @param  float  $amount  จำนวนเงิน
     * @param  string  $subType  ประเภทย่อย
     * @param  string|null  $sourceType  ที่มา
     * @param  int|null  $sourceId  ID ที่มา
     * @param  array  $metadata  ข้อมูลเพิ่มเติม
     */
    public function addFunds(
        float $amount,
        string $subType,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $metadata = []
    ): PlatformTransaction {
        return DB::transaction(function () use ($amount, $subType, $sourceType, $sourceId, $metadata) {
            // Lock wallet เพื่อให้ balance_before/after ถูกต้อง
            $wallet = static::lockForUpdate()->find($this->id);

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            // อัพเดทยอด (ใช้ $wallet ที่ lock แล้ว)
            $wallet->increment('balance', $amount);
            $wallet->increment('total_income', $amount);

            // สร้าง transaction
            return $this->transactions()->create([
                'type' => 'income',
                'sub_type' => $subType,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);
        });
    }

    /**
     * หักเงินจากกระเป๋า
     *
     * @param  float  $amount  จำนวนเงิน
     * @param  string  $subType  ประเภทย่อย
     * @param  string|null  $sourceType  ที่มา
     * @param  int|null  $sourceId  ID ที่มา
     * @param  array  $metadata  ข้อมูลเพิ่มเติม
     *
     * @throws \Exception
     */
    public function deductFunds(
        float $amount,
        string $subType,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $metadata = []
    ): PlatformTransaction {
        return DB::transaction(function () use ($amount, $subType, $sourceType, $sourceId, $metadata) {
            // Lock wallet เพื่อป้องกัน race condition
            $wallet = static::lockForUpdate()->find($this->id);

            if ($wallet->balance < $amount) {
                throw new \Exception('ยอดเงินในกระเป๋าไม่เพียงพอ');
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            // อัพเดทยอด (ใช้ $wallet ที่ lock แล้ว)
            $wallet->decrement('balance', $amount);
            $wallet->increment('total_expense', $amount);

            // สร้าง transaction
            return $this->transactions()->create([
                'type' => 'expense',
                'sub_type' => $subType,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);
        });
    }

    /**
     * รายได้วันนี้
     */
    public function getTodayIncomeAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'income')
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    /**
     * รายได้เดือนนี้
     */
    public function getThisMonthIncomeAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'income')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Label สี ตามประเภท
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PLATFORM_FEE => 'purple',
            self::TYPE_VAT => 'blue',
            self::TYPE_MLM_POOL => 'green',
            self::TYPE_RESERVE => 'yellow',
            self::TYPE_REFUND_POOL => 'red',
            default => 'gray',
        };
    }

    /**
     * Label ไทย ตามประเภท
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PLATFORM_FEE => 'ค่าธรรมเนียม',
            self::TYPE_VAT => 'ภาษี VAT',
            self::TYPE_MLM_POOL => 'กองทุน MLM',
            self::TYPE_RESERVE => 'เงินสำรอง',
            self::TYPE_REFUND_POOL => 'กองทุนคืนเงิน',
            default => 'อื่นๆ',
        };
    }
}

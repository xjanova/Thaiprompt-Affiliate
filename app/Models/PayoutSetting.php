<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * PayoutSetting Model
 *
 * ตั้งค่าระบบจ่ายเงินอัจฉริยะ
 *
 * @property int $id
 * @property string $payout_type
 * @property string $mode
 * @property int|null $schedule_day
 * @property string|null $schedule_time
 * @property string $schedule_frequency
 * @property int|null $schedule_day_of_week
 * @property float $min_payout_amount
 * @property float|null $max_payout_amount
 * @property int $holding_days
 * @property bool $require_approval
 * @property float|null $auto_approve_under
 * @property bool $auto_approve_verified_users
 * @property float $withdrawal_fee_fixed
 * @property float $withdrawal_fee_percent
 * @property bool $notify_on_payout
 * @property bool $notify_on_pending
 * @property bool $notify_admin_large_payout
 * @property float $large_payout_threshold
 * @property bool $is_active
 * @property string|null $description
 * @property array|null $extra_settings
 */
class PayoutSetting extends Model
{
    protected $fillable = [
        'payout_type',
        'mode',
        'schedule_day',
        'schedule_time',
        'schedule_frequency',
        'schedule_day_of_week',
        'min_payout_amount',
        'max_payout_amount',
        'holding_days',
        'require_approval',
        'auto_approve_under',
        'auto_approve_verified_users',
        'withdrawal_fee_fixed',
        'withdrawal_fee_percent',
        'notify_on_payout',
        'notify_on_pending',
        'notify_admin_large_payout',
        'large_payout_threshold',
        'is_active',
        'description',
        'extra_settings',
        // คอลัมน์เพิ่มเติมสำหรับ PlatformRevenueSeeder
        'slug',
        'name',
        'earning_type',
        'payout_mode',
        'min_payout',
        'max_payout',
        'fee_fixed',
        'fee_percentage',
        'requires_approval',
        'schedule',
    ];

    protected $casts = [
        'schedule_day' => 'integer',
        'schedule_day_of_week' => 'integer',
        'min_payout_amount' => 'decimal:2',
        'max_payout_amount' => 'decimal:2',
        'holding_days' => 'integer',
        'require_approval' => 'boolean',
        'auto_approve_under' => 'decimal:2',
        'auto_approve_verified_users' => 'boolean',
        'withdrawal_fee_fixed' => 'decimal:2',
        'withdrawal_fee_percent' => 'decimal:2',
        'notify_on_payout' => 'boolean',
        'notify_on_pending' => 'boolean',
        'notify_admin_large_payout' => 'boolean',
        'large_payout_threshold' => 'decimal:2',
        'is_active' => 'boolean',
        'extra_settings' => 'array',
        // casts เพิ่มเติมสำหรับ PlatformRevenueSeeder
        'min_payout' => 'decimal:2',
        'max_payout' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
        'requires_approval' => 'boolean',
        'schedule' => 'array',
    ];

    /**
     * ประเภทการจ่าย
     */
    const TYPE_SELLER = 'seller';

    const TYPE_MLM = 'mlm';

    const TYPE_SERVICE = 'service';

    /**
     * โหมดการจ่าย
     */
    const MODE_MANUAL = 'manual';

    const MODE_AUTO_SCHEDULE = 'auto_schedule';

    const MODE_REALTIME = 'realtime';

    const MODE_HYBRID = 'hybrid';

    /**
     * ความถี่
     */
    const FREQ_DAILY = 'daily';

    const FREQ_WEEKLY = 'weekly';

    const FREQ_BIWEEKLY = 'biweekly';

    const FREQ_MONTHLY = 'monthly';

    /**
     * ดึงการตั้งค่าตามประเภท
     */
    public static function getByType(string $type): ?self
    {
        return Cache::remember("payout_setting_{$type}", 3600, function () use ($type) {
            return static::where('payout_type', $type)->first();
        });
    }

    /**
     * ดึงหรือสร้างการตั้งค่าสำหรับ Seller
     */
    public static function getSellerSetting(): self
    {
        return static::firstOrCreate(
            ['payout_type' => self::TYPE_SELLER],
            [
                'mode' => self::MODE_MANUAL,
                'schedule_frequency' => self::FREQ_MONTHLY,
                'schedule_day' => 1,
                'min_payout_amount' => 100,
                'holding_days' => 7,
                'require_approval' => true,
                'notify_on_payout' => true,
                'notify_on_pending' => true,
                'description' => 'ตั้งค่าการจ่ายเงินให้ผู้ขาย',
            ]
        );
    }

    /**
     * ดึงหรือสร้างการตั้งค่าสำหรับ MLM
     */
    public static function getMlmSetting(): self
    {
        return static::firstOrCreate(
            ['payout_type' => self::TYPE_MLM],
            [
                'mode' => self::MODE_MANUAL,
                'schedule_frequency' => self::FREQ_WEEKLY,
                'schedule_day_of_week' => 1, // วันจันทร์
                'min_payout_amount' => 50,
                'holding_days' => 3,
                'require_approval' => true,
                'notify_on_payout' => true,
                'notify_on_pending' => true,
                'description' => 'ตั้งค่าการจ่ายคอมมิชชัน MLM',
            ]
        );
    }

    /**
     * ดึงหรือสร้างการตั้งค่าสำหรับ Service Provider
     */
    public static function getServiceSetting(): self
    {
        return static::firstOrCreate(
            ['payout_type' => self::TYPE_SERVICE],
            [
                'mode' => self::MODE_MANUAL,
                'schedule_frequency' => self::FREQ_MONTHLY,
                'schedule_day' => 1,
                'min_payout_amount' => 100,
                'holding_days' => 7,
                'require_approval' => true,
                'notify_on_payout' => true,
                'notify_on_pending' => true,
                'description' => 'ตั้งค่าการจ่ายเงินให้ผู้ให้บริการ',
            ]
        );
    }

    /**
     * ล้าง cache
     */
    public static function clearCache(): void
    {
        Cache::forget('payout_setting_'.self::TYPE_SELLER);
        Cache::forget('payout_setting_'.self::TYPE_MLM);
        Cache::forget('payout_setting_'.self::TYPE_SERVICE);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * คำนวณค่าธรรมเนียมการถอน
     */
    public function calculateWithdrawalFee(float $amount): float
    {
        $fixedFee = $this->withdrawal_fee_fixed ?? 0;
        $percentFee = $amount * (($this->withdrawal_fee_percent ?? 0) / 100);

        return $fixedFee + $percentFee;
    }

    /**
     * ตรวจสอบว่าต้องอนุมัติก่อนจ่ายหรือไม่
     */
    public function needsApproval(float $amount, ?User $user = null): bool
    {
        // ถ้าปิด require_approval ไม่ต้องอนุมัติ
        if (! $this->require_approval) {
            return false;
        }

        // ถ้ายอดน้อยกว่า auto_approve_under อนุมัติอัตโนมัติ
        if ($this->auto_approve_under && $amount < $this->auto_approve_under) {
            return false;
        }

        // ถ้า user verified และเปิด auto_approve_verified_users
        if ($user && $this->auto_approve_verified_users && $user->is_verified) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าเป็นยอดใหญ่ที่ต้องแจ้ง Admin หรือไม่
     */
    public function isLargePayout(float $amount): bool
    {
        return $this->notify_admin_large_payout
            && $amount >= ($this->large_payout_threshold ?? 10000);
    }

    /**
     * Label โหมด
     */
    public function getModeLabelAttribute(): string
    {
        return match ($this->mode) {
            self::MODE_MANUAL => 'อนุมัติด้วยตนเอง',
            self::MODE_AUTO_SCHEDULE => 'อัตโนมัติตามกำหนด',
            self::MODE_REALTIME => 'จ่ายทันที',
            self::MODE_HYBRID => 'ผสม (อัตโนมัติ/อนุมัติ)',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Label ความถี่
     */
    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->schedule_frequency) {
            self::FREQ_DAILY => 'ทุกวัน',
            self::FREQ_WEEKLY => 'ทุกสัปดาห์',
            self::FREQ_BIWEEKLY => 'ทุก 2 สัปดาห์',
            self::FREQ_MONTHLY => 'ทุกเดือน',
            default => 'ไม่ระบุ',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * StakingCoinSetting Model
 *
 * จัดการตั้งค่าระบบ Staking ด้วย Coin
 * รวมถึงอัตราแลกเปลี่ยน Coin -> THB และการตั้งค่าความเสี่ยง
 *
 * @property int $id
 * @property float $coin_to_thb_rate อัตราแลกเปลี่ยน: 1 Coin = X บาท
 * @property bool $allow_coin_staking เปิดใช้งานการลงทุนด้วย Coin
 * @property bool $allow_mixed_payment อนุญาตให้ใช้ Coin + เงินรวมกัน
 * @property float $min_coin_percentage สัดส่วน Coin ขั้นต่ำ (%)
 * @property float $max_coin_percentage สัดส่วน Coin สูงสุด (%)
 * @property bool $require_risk_acknowledgment บังคับให้ยอมรับความเสี่ยงก่อนลงทุน
 * @property string|null $default_risk_warning ข้อความเตือนความเสี่ยงเริ่มต้น
 * @property bool $is_active สถานะใช้งาน
 * @property int|null $updated_by ผู้อัพเดทล่าสุด
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @version 3.296.0
 * @since 2025-12-02
 */
class StakingCoinSetting extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'staking_coin_settings';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'coin_to_thb_rate',
        'allow_coin_staking',
        'allow_mixed_payment',
        'min_coin_percentage',
        'max_coin_percentage',
        'require_risk_acknowledgment',
        'default_risk_warning',
        'is_active',
        'updated_by',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'coin_to_thb_rate' => 'decimal:4',
        'allow_coin_staking' => 'boolean',
        'allow_mixed_payment' => 'boolean',
        'min_coin_percentage' => 'decimal:2',
        'max_coin_percentage' => 'decimal:2',
        'require_risk_acknowledgment' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * ข้อความเตือนความเสี่ยงเริ่มต้น
     *
     * @var string
     */
    const DEFAULT_RISK_WARNING = '⚠️ คำเตือน: การลงทุนมีความเสี่ยง ผลตอบแทนที่ผ่านมาไม่ได้รับประกันผลตอบแทนในอนาคต กรุณาศึกษาข้อมูลและลงทุนด้วยเงินที่คุณพร้อมจะสูญเสียได้ การลงทุนในแผนนี้ไม่ได้รับการคุ้มครองจากกองทุนคุ้มครองเงินฝาก';

    /**
     * ดึง instance ที่ active อยู่ (Singleton pattern)
     *
     * @return static
     *
     * @example
     * $settings = StakingCoinSetting::active();
     * $rate = $settings->coin_to_thb_rate;
     */
    public static function active(): static
    {
        $instance = static::where('is_active', true)->first();

        if (!$instance) {
            // สร้าง default settings ถ้ายังไม่มี
            $instance = static::create([
                'coin_to_thb_rate' => 1.0000,
                'allow_coin_staking' => true,
                'allow_mixed_payment' => true,
                'min_coin_percentage' => 0,
                'max_coin_percentage' => 100,
                'require_risk_acknowledgment' => true,
                'default_risk_warning' => static::DEFAULT_RISK_WARNING,
                'is_active' => true,
            ]);
        }

        return $instance;
    }

    /**
     * แปลง Coin เป็น THB
     *
     * @param float $coinAmount จำนวน Coin
     * @return float จำนวน THB
     *
     * @example
     * $thb = StakingCoinSetting::active()->convertCoinToThb(100); // 100 Coin = 100 บาท (ถ้า rate = 1)
     */
    public function convertCoinToThb(float $coinAmount): float
    {
        return $coinAmount * $this->coin_to_thb_rate;
    }

    /**
     * แปลง THB เป็น Coin
     *
     * @param float $thbAmount จำนวน THB
     * @return float จำนวน Coin
     *
     * @example
     * $coin = StakingCoinSetting::active()->convertThbToCoin(100); // 100 บาท = 100 Coin (ถ้า rate = 1)
     */
    public function convertThbToCoin(float $thbAmount): float
    {
        if ($this->coin_to_thb_rate <= 0) {
            return 0;
        }

        return $thbAmount / $this->coin_to_thb_rate;
    }

    /**
     * ตรวจสอบว่าสามารถใช้ Coin ลงทุนได้หรือไม่
     *
     * @return bool
     */
    public function canUseCoinForStaking(): bool
    {
        return $this->is_active && $this->allow_coin_staking;
    }

    /**
     * ตรวจสอบว่าสัดส่วน Coin ถูกต้องหรือไม่
     *
     * @param float $coinPercentage สัดส่วน Coin ที่ต้องการใช้ (%)
     * @return array ['valid' => bool, 'message' => string|null]
     *
     * @example
     * $result = $settings->validateCoinPercentage(50);
     * if (!$result['valid']) { echo $result['message']; }
     */
    public function validateCoinPercentage(float $coinPercentage): array
    {
        if ($coinPercentage < $this->min_coin_percentage) {
            return [
                'valid' => false,
                'message' => "สัดส่วน Coin ต้องไม่น้อยกว่า {$this->min_coin_percentage}%",
            ];
        }

        if ($coinPercentage > $this->max_coin_percentage) {
            return [
                'valid' => false,
                'message' => "สัดส่วน Coin ต้องไม่เกิน {$this->max_coin_percentage}%",
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * ดึงข้อความเตือนความเสี่ยง
     *
     * @return string
     */
    public function getRiskWarningAttribute(): string
    {
        return $this->default_risk_warning ?? static::DEFAULT_RISK_WARNING;
    }

    /**
     * ความสัมพันธ์กับผู้อัพเดทล่าสุด
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * อัพเดทการตั้งค่า
     *
     * @param array $data ข้อมูลที่จะอัพเดท
     * @param int|null $userId ID ของผู้อัพเดท
     * @return bool
     *
     * @example
     * $settings->updateSettings(['coin_to_thb_rate' => 0.5], auth()->id());
     */
    public function updateSettings(array $data, ?int $userId = null): bool
    {
        $data['updated_by'] = $userId;

        return $this->update($data);
    }
}
